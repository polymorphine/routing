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
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;


class CallbackGatewayTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->gate());
    }

    public function testCallbackPreventsForwardingRequest()
    {
        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $this->gate()->forward($request, $prototype));
    }

    public function testCallbackForwardsRequest()
    {
        $request = new Doubles\FakeServerRequest('POST');
        $route   = new Doubles\MockedRoute($response = new Doubles\FakeResponse());
        $this->assertSame($response, $this->gate($route)->forward($request, new Doubles\FakeResponse()));
    }

    public function testSelectCallsWrappedRouteWithSameParameter()
    {
        $route = $this->gate()->select('some.name');
        $this->assertSame('some.name', $route->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = Doubles\MockedRoute::withUri($uri);
        $this->assertSame($uri, (string) $this->gate($route)->uri(new Doubles\FakeUri(), []));
    }

    public function testRoutesMethod_ReturnsUriTemplatesAssociatedToRoutePaths()
    {
        $result = $this->gate($route)->routes('foo.bar', Doubles\FakeUri::fromString('/foo/bar'));
        $this->assertSame($route->mappedPath, $result);
    }

    private function gate(?Route &$route = null)
    {
        $route = $route ?? Doubles\MockedRoute::response('default');
        $callback = function (ServerRequestInterface $request) {
            return $request->getMethod() === 'POST' ? $request : null;
        };
        return new Route\Gate\CallbackGateway($callback, $route);
    }
}
