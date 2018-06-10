<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Gate;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\CallbackGateway;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;
use Closure;


class CallbackGatewayTest extends TestCase
{
    private static $prototype;

    public static function setUpBeforeClass()
    {
        self::$prototype = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $route = $this->middleware());
        $this->assertInstanceOf(CallbackGateway::class, $route);
    }

    public function testClosurePreventsForwardingRequest()
    {
        $request = new FakeServerRequest();
        $this->assertSame(self::$prototype, $this->middleware()->forward($request, self::$prototype));
    }

    public function testMiddlewareForwardsRequest()
    {
        $request = new FakeServerRequest('POST');
        $this->assertNotSame(self::$prototype, $this->middleware()->forward($request, self::$prototype));
    }

    public function testRouteCallsWrappedRouteWithSameParameter()
    {
        $this->assertInstanceOf(Route::class, $route = $this->middleware()->route('some.name'));
        $this->assertSame('some.name', $route->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = new CallbackGateway($this->basicCallback(), new MockedRoute($uri));
        $this->assertSame($uri, (string) $route->uri(new FakeUri(), []));
    }

    private function middleware(Closure $callback = null)
    {
        if (!$callback) {
            $callback = $this->basicCallback();
        }
        return new CallbackGateway($callback, new MockedRoute('default'));
    }

    private function basicCallback()
    {
        return function (ServerRequestInterface $request, Closure $forward) {
            return $request->getMethod() === 'POST' ? $forward($request) : null;
        };
    }
}
