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
use Polymorphine\Routing\Map;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;
use Polymorphine\Routing\Tests\RoutingTestMethods;


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
        $router = $this->splitter([], new Doubles\MockedRoute(null, $uri = new Doubles\FakeUri()));
        $this->assertSame($uri, $router->uri(new Doubles\FakeUri(), []));
    }

    public function testSelectEndpointCall_ReturnsFoundRoute()
    {
        $splitter = $this->splitter($routes = [
            'A' => new Doubles\MockedRoute(),
            'B' => new Doubles\MockedRoute()
        ]);
        $this->assertSame($routes['A'], $splitter->select('A'));
        $this->assertSame($routes['B'], $splitter->select('B'));
    }

    public function testSelectSwitchCallWithMorePathSegments_AsksNextSwitch()
    {
        $splitter = $this->splitter($routes = [
            'A' => new Doubles\MockedRoute(),
            'B' => new Doubles\MockedRoute()
        ]);
        $selected = $splitter->select('A.nextA');
        $this->assertSame($routes['A']->subRoute, $selected);
        $this->assertSame('nextA', $routes['A']->path);

        $selected = $splitter->select('B.nextB.nextB2');
        $this->assertSame($routes['B']->subRoute, $selected);
        $this->assertSame('nextB.nextB2', $routes['B']->path);
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

    public function testRoutesMethod_AddsRouteTracedPathsToRoutingMap()
    {
        $splitter = $this->splitter([
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute(),
            new Doubles\MockedRoute()
        ], new Doubles\MockedRoute());

        $map   = new Map();
        $uri   = '/foo/bar';
        $trace = (new Map\Trace($map, Doubles\FakeUri::fromString($uri)))->nextHop('path');

        $splitter->routes($trace);
        $expected = [
            new Map\Path('path', '*', $uri),
            new Map\Path('path.foo', '*', $uri),
            new Map\Path('path.bar', '*', $uri),
            new Map\Path('path.0', '*', $uri)
        ];

        $this->assertEquals($expected, $map->paths());
    }

    public function testNameConflictWithinDefaultRoute_ThrowsException()
    {
        $splitter = $this->splitter([
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute(),
            new Doubles\MockedRoute()
        ], Doubles\MockedRoute::withTraceCallback(function (Map\Trace $trace) {
            $trace->nextHop('bar');
        }));

        $trace = (new Map\Trace(new Map(), new Doubles\FakeUri()))->nextHop('path');
        $this->expectException(Exception\UnreachableEndpointException::class);
        $splitter->routes($trace);
    }

    private function splitter(array $routes = [], Route $default = null)
    {
        $routes = $routes ?: ['dummy' => new Doubles\MockedRoute()];
        return $default ? new Route\Splitter\ScanSwitch($routes, $default) : new Route\Splitter\ScanSwitch($routes);
    }
}
