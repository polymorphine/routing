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
use Polymorphine\Routing\Tests;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;


class CallbackGatewayTest extends TestCase
{
    use Tests\RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->middleware());
    }

    public function testCallbackPreventsForwardingRequest()
    {
        $request = new Doubles\FakeServerRequest();
        $this->assertSame(self::$prototype, $this->middleware()->forward($request, self::$prototype));
    }

    public function testMiddlewareForwardsRequest()
    {
        $request = new Doubles\FakeServerRequest('POST');
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
        $route = new Route\Gate\CallbackGateway($this->basicCallback(), Doubles\MockedRoute::withUri($uri));
        $this->assertSame($uri, (string) $route->uri(new Doubles\FakeUri(), []));
    }

    public function testRoutesMethod_ReturnsUriTemplatesAssociatedToRoutePaths()
    {
        $this->assertSame([], $this->middleware()->routes('foo.bar', Doubles\FakeUri::fromString('/foo/bar')));
    }

    private function middleware(callable $callback = null)
    {
        return new Route\Gate\CallbackGateway($callback ?: $this->basicCallback(), Doubles\MockedRoute::response('default'));
    }

    private function basicCallback()
    {
        return function (ServerRequestInterface $request) {
            return $request->getMethod() === 'POST' ? $request : null;
        };
    }
}
