<?php declare(strict_types=1);

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
use Polymorphine\Routing\Tests\Doubles;
use Polymorphine\Routing\Tests\RoutingTestMethods;


class ScanSwitchTest extends TestCase
{
    use RoutingTestMethods;

    public function test_Instantiation()
    {
        $this->assertInstanceOf(Route::class, $this->splitter());
    }

    public function test_Forward_NotMatchingRequest_ReturnsPrototypeInstance()
    {
        $splitter = $this->splitter();
        $this->assertSame(self::$prototype, $splitter->forward(new Doubles\FakeServerRequest(), self::$prototype));

        $splitter = $this->splitter(['name' => new Doubles\MockedRoute()]);
        $this->assertSame(self::$prototype, $splitter->forward(new Doubles\FakeServerRequest(), self::$prototype));
    }

    public function test_Forward_MatchingRequest_ReturnsEndpointResponse()
    {
        $splitter = $this->splitter(['name' => $this->responseRoute($response)]);
        $this->assertSame($response, $splitter->forward(new Doubles\FakeServerRequest(), self::$prototype));
    }

    public function test_Forward_ReturnsFirstMatchingEndpointResponse()
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

    public function test_Uri_WithoutDefinedDefaultRoute_ThrowsException()
    {
        $this->expectException(Route\Exception\AmbiguousEndpointException::class);
        $this->splitter()->uri(new Doubles\FakeUri(), []);
    }

    public function test_Uri_IsCalledFromDefaultRoute()
    {
        $router = $this->splitter([], new Doubles\MockedRoute(null, $uri = new Doubles\FakeUri()));
        $this->assertSame($uri, $router->uri(new Doubles\FakeUri(), []));
    }

    public function test_Select_Endpoint_ReturnsFoundRoute()
    {
        $splitter = $this->splitter($routes = [
            'A' => new Doubles\MockedRoute(),
            'B' => new Doubles\MockedRoute()
        ]);
        $this->assertSame($routes['A'], $splitter->select('A'));
        $this->assertSame($routes['B'], $splitter->select('B'));
    }

    public function test_Select_SwitchWithMorePathSegments_AsksNextSwitch()
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

    public function test_Select_WithEmptyPath_ThrowsException()
    {
        $this->expectException(Route\Exception\RouteNotFoundException::class);
        $this->splitter()->select('');
    }

    public function test_Select_WithUnknownPathName_ThrowsException()
    {
        $this->expectException(Route\Exception\RouteNotFoundException::class);
        $this->splitter()->select('NotDefined');
    }

    public function test_DefaultRouteIsScannedFirst()
    {
        $default  = $this->responseRoute($response);
        $splitter = $this->splitter([], $default);
        $this->assertSame($response, $splitter->forward(new Doubles\FakeServerRequest(), new Doubles\FakeResponse()));
    }

    public function test_Select_UnknownPathWhenDefaultRoutePresent_SelectsPathFromDefaultRoute()
    {
        $nested   = $this->splitter(['nested' => $subRoute = new Doubles\MockedRoute()]);
        $splitter = $this->splitter([], $nested);
        $this->assertSame($subRoute, $splitter->select('nested'));
    }

    public function test_Select_DefinedPathWhenDefaultRoutePresent_SelectsRouteForDefinedPath()
    {
        $nested   = $this->splitter(['route' => $subRoute = new Doubles\MockedRoute()]);
        $splitter = $this->splitter(['route' => $topRoute = new Doubles\MockedRoute()], $nested);
        $this->assertSame($topRoute, $splitter->select('route'));
    }

    public function test_Routes_AddsRouteTracedPathsToRoutingMap()
    {
        $splitter = $this->splitter([
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute(),
            0     => new Doubles\MockedRoute()
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

    public function test_NameConflictWithinDefaultRoute_ThrowsException()
    {
        $splitter = $this->splitter([
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute(),
            0     => new Doubles\MockedRoute()
        ], Doubles\MockedRoute::withTraceCallback(function (Map\Trace $trace) {
            $trace->nextHop('bar');
        }));

        $trace = (new Map\Trace(new Map(), new Doubles\FakeUri()))->nextHop('path');
        $this->expectException(Map\Exception\UnreachableEndpointException::class);
        $splitter->routes($trace);
    }

    private function splitter(array $routes = [], ?Route $default = null)
    {
        $routes = $routes ?: ['dummy' => new Doubles\MockedRoute()];
        return $default ? new Route\Splitter\ScanSwitch($routes, $default) : new Route\Splitter\ScanSwitch($routes);
    }
}
