<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Tests\Doubles\FakeResponseFactory;
use Polymorphine\Routing\Tests\Doubles\FakeUriFactory;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;


class RouterTest extends TestCase
{
    private static $prototype;

    public static function setUpBeforeClass()
    {
        self::$prototype = new Doubles\FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Router::class, $this->router());
        $this->assertInstanceOf(Router::class, Router::withPrototypeFactories(
            new MockedRoute(),
            new FakeUriFactory(),
            new FakeResponseFactory()
        ));
    }

    public function testNotMatchedRequestDispatch_ReturnsPrototypeInstance()
    {
        $router = $this->router(false);
        $this->assertSame(self::$prototype, $router->handle(new Doubles\FakeServerRequest()));
    }

    public function testMatchingRequestDispatch_ReturnsEndpointResponse()
    {
        $router = $this->router(true);
        $this->assertNotSame(self::$prototype, $router->handle(new Doubles\FakeServerRequest()));
        $this->assertSame('matched', $router->handle(new Doubles\FakeServerRequest())->body);
    }

    public function testUri_ReturnsUriBasedOnDefault()
    {
        $router = $this->router(false, $uri = new Doubles\FakeUri());
        $this->assertSame($uri, $router->uri('anything'));

        $router = $this->router(true, $uri);
        $this->assertNotSame($uri, $response = $router->uri('anything'));
        $this->assertEquals($uri->withPath('matched'), $response);
    }

    public function testSelectMethod_ReturnsRouterInstanceWithNewRootRoute()
    {
        $router = new Router(
            $route = Doubles\MockedRoute::response('matched'),
            $uri ?? new Doubles\FakeUri(),
            self::$prototype
        );

        $route->path = 'root.context';

        $router = $router->select('new.context');
        $this->assertInstanceOf(Router::class, $router);
        $this->assertSame('new.context', $route->path);
    }

    public function testRoutesMethod_ReturnsRoutesArrayFromRoutes()
    {
        $uri    = Doubles\FakeUri::fromString('/foo/bar');
        $router = new Router(new Doubles\MockedRoute(), $uri, self::$prototype);
        $this->assertSame(['end' => (string) $uri], $router->routes());
    }

    private function router(bool $matched = true, $uri = null)
    {
        $response = $matched ? new Doubles\FakeResponse('matched') : null;
        $routeUri = $matched ? Doubles\FakeUri::fromString('matched') : null;

        return new Router(
            new Doubles\MockedRoute($response, $routeUri),
            $uri ?? new Doubles\FakeUri(),
            self::$prototype
        );
    }
}
