<?php

namespace Zycon42\ParamConverters\Application;

use Zycon42\ParamConverters\ParamConvertersManager;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Http\Session;
use Nette\Object;
use Nette\Security\User;
use Nette\Utils\Random;

/**
 * Storage for requests in session.
 * Applies param converters if needed.
 *
 * This is slightly modified version of enumag/Application RequestStorage
 * @see https://github.com/enumag/Application/blob/master/src/RequestStorage.php
 * @author Jáchym Toušek
 * @author Jan Dušek
 */
class RequestStorage extends Object {

    const SESSION_SECTION = 'Zycon42.ParamConverters.Application/requests';

    /** @var User */
    private $user;

    /** @var ParamConvertersManager */
    private $convertersManager;

    /** @var Session */
    private $session;

    public function __construct(User $user, Session $session, ParamConvertersManager $convertersManager = null) {
        $this->user = $user;
        $this->session = $session;
        $this->convertersManager = $convertersManager;
    }

    /**
     * Stores request into storage
     * @param Request $request
     * @param string $expiration
     * @return string unique key that represents stored request
     */
    public function storeRequest(Request $request, $expiration = '+ 10 minutes') {
        $request = clone $request;

        if ($this->convertersManager) {
            $this->convertersManager->convertBack($request);
        }

        $session = $this->session->getSection(self::SESSION_SECTION);
        do {
            $key = Random::generate(5);
        } while (isset($session[$key]));

        $session[$key] = [$this->user->getId(), $request];
        $session->setExpiration($expiration, $key);
        return $key;
    }

    /**
     * Loads request from storage.
     * Applies param converters if needed
     * @param string $key unique key that represents request in storage
     * @param bool $remove flag if you want to remove request from storage default is true
     * @return Request|null
     */
    public function loadRequest($key, $remove = true) {
        $session = $this->session->getSection(self::SESSION_SECTION);
        if (!isset($session[$key]) || ($session[$key][0] !== null && $session[$key][0] !== $this->user->getId())) {
            return null;
        }

        $request = $session[$key][1];
        if ($remove) {
            unset($session[$key]);
        }

        if ($this->convertersManager) {
            try {
                $this->convertersManager->convert($request);
            } catch (BadRequestException $e) {
                return null;
            }
        }

        $request->setFlag(Request::RESTORED, TRUE);
        return $request;
    }
}
