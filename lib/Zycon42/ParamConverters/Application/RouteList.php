<?php

namespace Zycon42\ParamConverters\Application;

use Zycon42\ParamConverters\ParamConvertersManager;
use Nette\Application\Request;
use Nette\Application\Routers\RouteList as NetteRouteList;
use Nette\Http\IRequest;
use Nette\Http\Url;

/**
 * This is slightly modified version of Arachne EntityLoader RouteList
 * @see https://gist.github.com/enumag/1a50f16f95fd73a330cc
 * @author Jáchym Toušek
 * @author Jan Dušek
 */
class RouteList extends NetteRouteList {

    /** @var ParamConvertersManager */
    private $convertersManager;

    public function __construct(ParamConvertersManager $convertersManager, $module = null) {
        parent::__construct($module);
        $this->convertersManager = $convertersManager;
    }

    /**
     * @param IRequest $httpRequest
     * @return \Nette\Application\Request|NULL
     */
    public function match(IRequest $httpRequest) {
        $request = parent::match($httpRequest);
        if ($request) {
            $this->convertersManager->convert($request);
        }
        return $request;
    }

    /**
     * @param Request $appRequest
     * @param Url $refUrl
     * @return NULL|string
     */
    public function constructUrl(Request $appRequest, Url $refUrl) {
        $originalRequestParams = $appRequest->parameters;
        $this->convertersManager->convertBack($appRequest);
        $url = parent::constructUrl($appRequest, $refUrl);
        $appRequest->setParameters($originalRequestParams);
        return $url;
    }
}
