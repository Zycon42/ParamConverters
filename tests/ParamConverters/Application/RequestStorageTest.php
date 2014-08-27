<?php

namespace Zycon42\ParamConverters\Tests\Application;

use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Security\User;
use Zycon42\ParamConverters\Application\RequestStorage;
use Zycon42\ParamConverters\ParamConvertersManager;

class RequestStorageTest extends \PHPUnit_Framework_TestCase {

    /** @var RequestStorage */
    private $requestStorage;

    /** @var \Mockery\MockInterface */
    private $user;

    /** @var \Mockery\MockInterface */
    private $session;

    /** @var \Mockery\MockInterface */
    private $convertersManager;

    protected function setUp() {
        $this->user = \Mockery::mock(User::class);
        $this->session = \Mockery::mock(Session::class);
        $this->convertersManager = \Mockery::mock(ParamConvertersManager::class);

        $this->requestStorage = new RequestStorage($this->user, $this->session, $this->convertersManager);
    }

    protected function tearDown() {
        \Mockery::close();
    }

    public function testLoadRequest_keyNotInSession_nullReturned() {
        $session = \Mockery::mock(SessionSection::class);
        $this->session->shouldReceive('getSection')->with(RequestStorage::SESSION_SECTION)
            ->andReturn($session);

        $session->shouldReceive('offsetExists')->with('foo')->andReturn(false);

        $result = $this->requestStorage->loadRequest('foo');

        $this->assertEquals(null, $result);
    }

    public function testLoadRequest_keyNotForCurrentUser_nullReturned() {
        $session = \Mockery::mock(SessionSection::class);
        $this->session->shouldReceive('getSection')->with(RequestStorage::SESSION_SECTION)
            ->andReturn($session);

        $session->shouldReceive('offsetExists')->with('foo')->andReturn(true);
        $session->shouldReceive('offsetGet')->with('foo')->andReturn([ 2, null ]);

        $this->user->shouldReceive('getId')->andReturn(1);

        $result = $this->requestStorage->loadRequest('foo');

        $this->assertEquals(null, $result);
    }

    public function testLoadRequest_converterThrowsBadRequestException_nullReturned() {
        $session = \Mockery::mock(SessionSection::class);
        $this->session->shouldReceive('getSection')->with(RequestStorage::SESSION_SECTION)
            ->andReturn($session);

        $session->shouldReceive('offsetExists')->with('foo')->andReturn(true);

        $request = \Mockery::mock(Request::class);
        $session->shouldReceive('offsetGet')->with('foo')->andReturn([ 1, $request ]);

        $this->user->shouldReceive('getId')->andReturn(1);

        $this->convertersManager->shouldReceive('convert')->with($request)->andThrow(BadRequestException::class);

        $result = $this->requestStorage->loadRequest('foo', false);

        $this->assertEquals(null, $result);
    }

    public function testLoadRequest_removeRequestFlagIsTrue_requestWithGivenKeyRemoved() {
        $session = \Mockery::mock(SessionSection::class);
        $this->session->shouldReceive('getSection')->with(RequestStorage::SESSION_SECTION)
            ->andReturn($session);

        $session->shouldReceive('offsetExists')->with('foo')->andReturn(true);

        $request = \Mockery::mock(Request::class)->shouldIgnoreMissing();
        $session->shouldReceive('offsetGet')->with('foo')->andReturn([ 1, $request ]);

        $this->user->shouldReceive('getId')->andReturn(1);

        $session->shouldReceive('offsetUnset')->with('foo')->once();

        $this->convertersManager->shouldIgnoreMissing();

        $this->requestStorage->loadRequest('foo');
    }

    public function testLoadRequest_removeRequestFlagIsFalse_requestNotRemoved() {
        $session = \Mockery::mock(SessionSection::class);
        $this->session->shouldReceive('getSection')->with(RequestStorage::SESSION_SECTION)
            ->andReturn($session);

        $session->shouldReceive('offsetExists')->with('foo')->andReturn(true);

        $request = \Mockery::mock(Request::class)->shouldIgnoreMissing();
        $session->shouldReceive('offsetGet')->with('foo')->andReturn([ 1, $request ]);

        $this->user->shouldReceive('getId')->andReturn(1);

        $session->shouldReceive('offsetUnset')->with('foo')->never();

        $this->convertersManager->shouldIgnoreMissing();

        $this->requestStorage->loadRequest('foo', false);
    }

    public function testLoadRequest_requestLoaded_requestRestoredFlagSetToTrue() {
        $session = \Mockery::mock(SessionSection::class);
        $this->session->shouldReceive('getSection')->with(RequestStorage::SESSION_SECTION)
            ->andReturn($session);

        $session->shouldReceive('offsetExists')->with('foo')->andReturn(true);

        $request = \Mockery::mock(Request::class)->shouldIgnoreMissing();
        $request->shouldReceive('setFlag')->with(Request::RESTORED, true)->once();

        $session->shouldReceive('offsetGet')->with('foo')->andReturn([ 1, $request ]);

        $this->user->shouldReceive('getId')->andReturn(1);

        $session->shouldReceive('offsetUnset')->with('foo');

        $this->convertersManager->shouldIgnoreMissing();

        $this->requestStorage->loadRequest('foo');
    }
}
