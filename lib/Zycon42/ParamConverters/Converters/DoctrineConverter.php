<?php

namespace Zycon42\ParamConverters\Converters;

use Kdyby\Doctrine\EntityManager;
use Zycon42\ParamConverters\Annotations\ParamConverter;
use Nette\Application\Request;
use Nette;

class DoctrineConverter extends Nette\Object implements IParamConverter {

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    public function convert(Request $request, ParamConverter $configuration) {
        $name = $configuration->name;
        $class = $configuration->class;
        $options = $this->getOptions($configuration);

        $id = $this->getIdentifier($request, $options);
        if ($id === null)
            return false;

        $value = $this->em->getRepository($class)->{$options['repositoryMethod']}($id);
        if (!$value) {
            throw new Nette\Application\BadRequestException('Entity with given id not found in repository');
        }

        $requestParams = $request->parameters;
        $requestParams[$name] = $value;
        $request->setParameters($requestParams);

        return true;
    }

    public function convertBack(Request $request, ParamConverter $configuration) {
        $name = $configuration->name;
        $class = $configuration->class;
        $options = $this->getOptions($configuration);

        $entity = $request->parameters[$name];
        if (!$entity || !$entity instanceof $class) {
            throw new \InvalidArgumentException('Valid entity not present in request params');
        }

        $idName = isset($options['id']) ? $options['id'] : 'id';

        $requestParams = $request->parameters;
        $requestParams[$idName] = $entity->id;
        unset($requestParams[$name]);
        $request->setParameters($requestParams);

        return true;
    }

    public function supports(ParamConverter $configuration) {
        if ($configuration->class === null)
            return false;

        // this checks if class is doctrine entity
        return !$this->em->getMetadataFactory()->isTransient($configuration->class);
    }

    private function getOptions(ParamConverter $config) {
        if ($config->options === null)
            return [
                'repositoryMethod' => 'find'
            ];
        return $config->options;
    }

    private function getIdentifier(Request $request, array $options) {
        if (isset($options['id'])) {
            $idName = $options['id'];
        } else
            $idName = 'id';

        if (!isset($request->parameters[$idName])) {
            throw new \InvalidArgumentException("Given id is not present in request");
        }

        return $request->parameters[$idName];
    }
}
