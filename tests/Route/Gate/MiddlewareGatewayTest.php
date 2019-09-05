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


class MiddlewareGatewayTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->middleware());
    }

    public function testMiddlewareForwardsRequest()
    {
        $prototype = new Doubles\FakeResponse();
        $request   = new Doubles\FakeServerRequest('POST');
        $response  = $this->middleware()->forward($request->withAttribute('middleware', 'processed'), $prototype);

        $this->assertNotSame($prototype, $response);
        $this->assertSame('processed: wrap response wrap', (string) $response->getBody());
    }

    public function testSelectCallsNextRouteWithSameParameter()
    {
        $route = $this->middleware()->select('some.name');
        $this->assertSame('some.name', $route->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = new Route\Gate\MiddlewareGateway(new Doubles\FakeMiddleware(), Doubles\MockedRoute::withUri($uri));
        $this->assertSame($uri, (string) $route->uri(new Doubles\FakeUri(), []));
    }

    public function testRoutesMethod_ReturnsUriTemplatesAssociatedToRoutePaths()
    {
        $this->assertSame([], $this->middleware()->routes('foo.bar', Doubles\FakeUri::fromString('/foo/bar')));
    }

    private function middleware()
    {
        return new Route\Gate\MiddlewareGateway(new Doubles\FakeMiddleware(), Doubles\MockedRoute::response('response'));
    }
}
