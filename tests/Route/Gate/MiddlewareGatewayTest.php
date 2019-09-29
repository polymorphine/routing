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
use Polymorphine\Routing\Map;
use Polymorphine\Routing\Tests\Doubles;


class MiddlewareGatewayTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->gate());
    }

    public function testMiddlewareForwardsRequest()
    {
        $request  = (new Doubles\FakeServerRequest('POST'))->withAttribute('middleware', 'processed');
        $response = $this->gate($route)->forward($request, new Doubles\FakeResponse());

        $this->assertSame($request, $route->forwardedRequest);
        $this->assertSame('processed: wrap response wrap', (string) $response->getBody());
    }

    public function testSelectCallsNextRouteWithSameParameter()
    {
        $this->gate($subRoute)->select('some.name');
        $this->assertSame('some.name', $subRoute->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = Doubles\MockedRoute::withUri($uri);
        $this->assertSame($uri, (string) $this->gate($route)->uri(new Doubles\FakeUri(), []));
    }

    public function testRoutesMethod_PassesTraceToNextRoute()
    {
        $trace = new Map\Trace(new Map(), new Doubles\FakeUri());
        $this->gate($route)->routes($trace);
        $this->assertSame($trace, $route->trace);
    }

    private function gate(?Route &$route = null)
    {
        $route = $route ?? new Doubles\MockedRoute(new Doubles\FakeResponse('response'), new Doubles\FakeUri());
        return new Route\Gate\MiddlewareGateway(new Doubles\FakeMiddleware(), $route);
    }
}
