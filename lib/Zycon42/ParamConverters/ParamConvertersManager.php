<?php

namespace Zycon42\ParamConverters;

use Doctrine\Common\Annotations\Reader;
use Zycon42\ParamConverters\Annotations\ParamConverter;
use Zycon42\ParamConverters\Converters\IParamConverter;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Nette\Object;

class ParamConvertersManager extends Object {

    /** @var IPresenterFactory */
    private $presenterFactory;

    /** @var Reader */
    private $annotationReader;

    /** @var IParamConverter[] */
    private $converters;

    public function __construct(IPresenterFactory $presenterFactory, Reader $annotationReader) {
        $this->presenterFactory = $presenterFactory;
        $this->annotationReader = $annotationReader;
    }

    /**
     * Converts request parameters.
     * Converted values are added to request parameters.
     * @param Request $request
     */
    public function convert(Request $request) {
        $configurations = $this->readParameterConfigurations($request);

        foreach ($configurations as $paramName => $config) {
            $className = $config->class;

            // when parameter with expected class is already set we don't need to make conversion
            if (isset($request->parameters[$paramName]) && $request->parameters[$paramName] instanceof $className) {
                return;
            }

            foreach ($this->converters as $converter) {
                if ($converter->supports($config)) {
                    if ($converter->convert($request, $config)) {
                        return;
                    }
                }
            }
        }
    }

    /**
     * Deletes converted parameters from request.
     * @param Request $request
     */
    public function convertBack(Request $request) {
        $configurations = $this->readParameterConfigurations($request);

        foreach ($configurations as $paramName => $config) {

            // when param already deleted from request params then we don't need to make conversion
            if (!isset($request->parameters[$paramName])) {
                return;
            }

            foreach ($this->converters as $converter) {
                if ($converter->supports($config)) {
                    if ($converter->convertBack($request, $config)) {
                        return;
                    }
                }
            }
        }
    }

    public function addConverter(IParamConverter $converter) {
        $this->converters[] = $converter;
    }

    /**
     * Reads request param configurations from annotations above according presenter action method.
     * @param Request $request
     * @return array
     */
    private function readParameterConfigurations(Request $request) {
        $presenterName = $request->getPresenterName();
        $presenterClass = $this->presenterFactory->getPresenterClass($presenterName);

        $classReflex = new \ReflectionClass($presenterClass);
        $configurations = [];

        $action = isset($request->parameters[Presenter::ACTION_KEY]) ?
            $request->parameters[Presenter::ACTION_KEY] : Presenter::DEFAULT_ACTION;

        $actionMethod = Presenter::formatActionMethod($action);
        if ($classReflex->hasMethod($actionMethod)) {
            $this->readMethodConfigurations($classReflex->getMethod($actionMethod), $configurations);
        }

        // if we have signal
        if (isset($request->parameters[Presenter::SIGNAL_KEY])) {
            $signal = $request->parameters[Presenter::SIGNAL_KEY];

            // we want only signals for presenter
            if (!strpos($signal, '-')) {
                $signalMethod = Presenter::formatSignalMethod($signal);
                if ($classReflex->hasMethod($signalMethod)) {
                    $this->readMethodConfigurations($classReflex->getMethod($signalMethod), $configurations);
                }
            }
        }

        return $configurations;
    }

    private function readMethodConfigurations(\ReflectionMethod $methodReflection, array &$configurations) {
        $actionParams = $methodReflection->getParameters();

        // create configurations from annotations first
        $annotations = $this->annotationReader->getMethodAnnotations($methodReflection);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ParamConverter) {
                if ($annotation->class === null) {
                    $annotation->class = $this->getParamClass($actionParams, $annotation->name);
                }

                $configurations[$annotation->name] = $annotation;
            }
        }

        // go through parameters and try to create configurations from param type hints
        foreach ($actionParams as $param) {
            $paramClass = $param->getClass();
            $paramName = $param->getName();
            // do it only if we don't have configuration for parameter already
            if ($paramClass && !isset($configurations[$paramName])) {
                $config = new ParamConverter();
                $config->name = $paramName;
                $config->class = $paramClass->getName();

                $configurations[$paramName] = $config;
            }
        }
    }

    /**
     * @param \ReflectionParameter[] $params
     * @param $name
     * @return string|null
     */
    private function getParamClass(array $params, $name) {
        $result = null;
        foreach ($params as $param) {
            if ($param->getName() === $name) {
                $paramClass = $param->getClass();
                if ($paramClass)
                    $result = $paramClass->getName();
            }
        }
        return $result;
    }
}
