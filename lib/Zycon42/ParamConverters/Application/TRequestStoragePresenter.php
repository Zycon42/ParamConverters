<?php

namespace Zycon42\ParamConverters\Application;

use Nette\Application\Responses;

/**
 * Use this trait if you want to use RequestStorage for storing requests in your presenters.
 *
 * This is slightly modified version of enumag/Application TRequestStoragePresenter
 * @see https://github.com/enumag/Application/blob/master/src/TRequestStoragePresenter.php
 * @author Jáchym Toušek
 * @author Jan Dušek
 */
trait TRequestStoragePresenter {

    /** @var RequestStorage */
    private $requestStorage;

    public function injectRequestStorage(RequestStorage $requestStorage) {
        $this->requestStorage = $requestStorage;
    }

    /**
     * Stores current request to session.
     * @param  mixed $expiration optional expiration time
     * @return string key
     */
    public function storeRequest($expiration = '+ 10 minutes') {
        return $this->requestStorage->storeRequest($this->request, $expiration);
    }

    /**
     * Restores request from session.
     * @param  string $key
     * @return void
     */
    public function restoreRequest($key) {
        $request = $this->requestStorage->loadRequest($key);
        if (!$request)
            return;

        $params = $request->getParameters();
        $params[self::FLASH_KEY] = $this->getParameter(self::FLASH_KEY);
        $request->setParameters($params);
        $this->sendResponse(new Responses\ForwardResponse($request));
    }
}
