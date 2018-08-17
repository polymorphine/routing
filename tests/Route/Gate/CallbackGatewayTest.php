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
use Polymorphine\Routing\Tests\EndpointTestMethods;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;


class CallbackGatewayTest extends TestCase
{
    use EndpointTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->middleware());
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

    public function testSelectCallsWrappedRouteWithSameParameter()
    {
        $route = $this->middleware()->select('some.name');
        $this->assertSame('some.name', $route->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = new CallbackGateway($this->basicCallback(), MockedRoute::withUri($uri));
        $this->assertSame($uri, (string) $route->uri(new FakeUri(), []));
    }

    private function middleware(callable $callback = null)
    {
        return new CallbackGateway($callback ?: $this->basicCallback(), MockedRoute::response('default'));
    }

    private function basicCallback()
    {
        return function (ServerRequestInterface $request) {
            return $request->getMethod() === 'POST' ? $request : null;
        };
    }
}
