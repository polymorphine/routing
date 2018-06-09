<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\MiddlewareGateway;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeMiddleware;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class MiddlewareGatewayTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $route = $this->middleware());
        $this->assertInstanceOf(MiddlewareGateway::class, $route);
    }

    public function testMiddlewareForwardsRequest()
    {
        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('POST');
        $response  = $this->middleware()->forward($request->withAttribute('middleware', 'processed'), $prototype);

        $this->assertNotSame($prototype, $response);
        $this->assertSame('processed: wrap response wrap', (string) $response->getBody());
    }

    public function testRouteCallsNextRouteWithSameParameter()
    {
        $this->assertInstanceOf(Route::class, $route = $this->middleware()->route('some.name'));
        $this->assertSame('some.name', $route->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = new MiddlewareGateway(new FakeMiddleware('wrap'), new MockedRoute($uri));
        $this->assertSame($uri, (string) $route->uri(new FakeUri(), []));
    }

    private function middleware()
    {
        return new MiddlewareGateway(new FakeMiddleware('wrap'), new MockedRoute('response'));
    }
}
