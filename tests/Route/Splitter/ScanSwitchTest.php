<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Splitter;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use Polymorphine\Routing\Tests\Doubles;


class ScanSwitchTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->splitter());
    }

    public function testForwardingNotMatchingRequest_ReturnsPrototypeInstance()
    {
        $splitter = $this->splitter();
        $this->assertSame(self::$prototype, $splitter->forward(new Doubles\FakeServerRequest(), self::$prototype));

        $splitter = $this->splitter(['name' => new Doubles\MockedRoute()]);
        $this->assertSame(self::$prototype, $splitter->forward(new Doubles\FakeServerRequest(), self::$prototype));
    }

    public function testForwardingMatchingRequest_ReturnsEndpointResponse()
    {
        $splitter = $this->splitter(['name' => $this->responseRoute($response)]);
        $this->assertSame($response, $splitter->forward(new Doubles\FakeServerRequest(), self::$prototype));
    }

    public function testForwardingRequest_ReturnsFirstMatchingEndpointResponse()
    {
        $routes = [
            'block' => new Doubles\MockedRoute(),
            'match' => $this->responseRoute($firstMatch),
            'last'  => $this->responseRoute($secondMatch)
        ];

        $splitter = $this->splitter($routes);
        $request  = new Doubles\FakeServerRequest();
        $this->assertSame($firstMatch, $splitter->forward($request, self::$prototype));
        $this->assertSame(self::$prototype, $routes['block']->forward($request, self::$prototype));
        $this->assertSame($secondMatch, $routes['last']->forward($request, self::$prototype));
    }

    public function testUriMethodWithoutDefinedDefaultRoute_ThrowsException()
    {
        $this->expectException(Exception\EndpointCallException::class);
        $this->splitter()->uri(new Doubles\FakeUri(), []);
    }

    public function testUriIsCalledFromDefaultRoute()
    {
        $router = $this->splitter([], Doubles\MockedRoute::withUri('/foo/bar'));
        $this->assertSame('http://example.com/foo/bar', (string) $router->uri(Doubles\FakeUri::fromString('http://example.com'), []));
    }

    public function testSelectEndpointCall_ReturnsFoundRoute()
    {
        $route = $this->splitter([
            'A' => $routeA = new Doubles\MockedRoute(),
            'B' => $routeB = new Doubles\MockedRoute()
        ]);
        $this->assertSame($routeA, $route->select('A'));
        $this->assertSame($routeB, $route->select('B'));
    }

    public function testSelectSwitchCallWithMorePathSegments_AsksNextSwitch()
    {
        $splitter = $this->splitter([
            'A' => $routeA = new Doubles\MockedRoute(),
            'B' => $routeB = new Doubles\MockedRoute()
        ]);
        $selected = $splitter->select('A.nextA');
        $this->assertSame($routeA, $selected);
        $this->assertSame('nextA', $selected->path);

        $selected = $splitter->select('B.nextB.nextB2');
        $this->assertSame($routeB, $selected);
        $this->assertSame('nextB.nextB2', $selected->path);
    }

    public function testSelectWithEmptyPath_ThrowsException()
    {
        $this->expectException(Exception\SwitchCallException::class);
        $this->splitter()->select('');
    }

    public function testSelectWithUnknownPathName_ThrowsException()
    {
        $this->expectException(Exception\SwitchCallException::class);
        $this->splitter()->select('NotDefined');
    }

    public function testDefaultRouteIsScannedFirst()
    {
        $default  = $this->responseRoute($response);
        $splitter = $this->splitter([], $default);
        $this->assertSame($response, $splitter->forward(new Doubles\FakeServerRequest(), new Doubles\FakeResponse()));
    }

    public function testSelectUnknownPathWhenDefaultRoutePresent_SelectsPathFromDefaultRoute()
    {
        $nested   = $this->splitter(['nested' => $subRoute = new Doubles\MockedRoute()]);
        $splitter = $this->splitter([], $nested);
        $this->assertSame($subRoute, $splitter->select('nested'));
    }

    public function testSelectDefinedPathWhenDefaultRoutePresent_SelectsRouteForDefinedPath()
    {
        $nested   = $this->splitter(['route' => $subRoute = new Doubles\MockedRoute()]);
        $splitter = $this->splitter(['route' => $topRoute = new Doubles\MockedRoute()], $nested);
        $this->assertSame($topRoute, $splitter->select('route'));
    }

    private function splitter(array $routes = [], Route $default = null)
    {
        $routes = $routes ?: ['dummy' => new Doubles\MockedRoute()];
        return $default ? new Route\Splitter\ScanSwitch($routes, $default) : new Route\Splitter\ScanSwitch($routes);
    }
}
