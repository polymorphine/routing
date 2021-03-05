<?php declare(strict_types=1);

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
use Polymorphine\Routing\Map\Path;
use Polymorphine\Routing\Route\Exception;
use InvalidArgumentException;


class RouterTest extends TestCase
{
    private static $prototype;

    public static function setUpBeforeClass(): void
    {
        self::$prototype = new Doubles\FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Router::class, $this->router());
        $this->assertInstanceOf(Router::class, Router::withPrototypeFactories(
            new Doubles\MockedRoute(),
            new Doubles\FakeUriFactory(),
            new Doubles\FakeResponseFactory()
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

    public function testUriWithRootPath_ReturnsUriDirectlyFromRoute()
    {
        $route  = new Doubles\MockedRoute();
        $router = new Router($route, new Doubles\FakeUri(), new Doubles\FakeResponse());
        $router->uri('ROOT');
        $this->assertNull($route->path);

        $router->uri('some.path');
        $this->assertEquals('some.path', $route->path);
    }

    public function testSelectMethod_ReturnsRouterInstanceWithNewRootRoute()
    {
        $route  = Doubles\MockedRoute::response('matched');
        $router = new Router($route, new Doubles\FakeUri(), self::$prototype);

        $route->path = 'root.context';

        $router = $router->select('new.context');
        $this->assertInstanceOf(Router::class, $router);
        $this->assertSame('new.context', $route->path);
    }

    public function testSelectRootRoute_ReturnsSameRouterInstance()
    {
        $router = new Router(new Doubles\MockedRoute(), new Doubles\FakeUri(), self::$prototype, 'home');
        $this->assertSame($router, $router->select('home'));
    }

    /**
     * @dataProvider routeExceptions
     *
     * @param \Exception $exception
     */
    public function testThrownExceptionIncludesRoutePathInfo(\Exception $exception)
    {
        $route = new Doubles\MockedRoute();
        $route->exception = $exception;

        $router = new Router($route, new Doubles\FakeUri(), self::$prototype);
        try {
            $router->uri('foo.bar.baz');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('test (called route: foo.bar.baz)', $e->getMessage());
            $this->assertInstanceOf(get_class($exception), $e);
        }
    }

    public function routeExceptions(): array
    {
        return [
            [new Exception\RouteNotFoundException('test')],
            [new Exception\AmbiguousEndpointException('test')],
            [new Exception\InvalidUriPrototypeException('test')],
            [new Exception\InvalidUriParamException('test')]
        ];
    }

    public function testRoutesMethod_ReturnsRoutePathsArray()
    {
        $router = new Router(new Doubles\MockedRoute(), Doubles\FakeUri::fromString('/foo/bar'), self::$prototype, 'home');
        $this->assertEquals([new Path('home', '*', '/foo/bar')], $router->routes());
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
