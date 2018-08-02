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
use Polymorphine\Routing\Route\Splitter\ResponseScanSwitch;
use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Exception\SwitchCallException;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;


class ResponseScanSwitchTest extends TestCase
{
    private static $prototype;

    public static function setUpBeforeClass()
    {
        self::$prototype = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testForwardingNotMatchingRequest_ReturnsPrototypeInstance()
    {
        $route = $this->route();
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest(), self::$prototype));

        $route = $this->route(['name' => new MockedRoute()]);
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest(), self::$prototype));
    }

    public function testForwardingMatchingRequest_ReturnsEndpointResponse()
    {
        $route = $this->route(['name' => MockedRoute::response('endpoint')]);
        $this->assertNotSame(self::$prototype, $route->forward(new FakeServerRequest(), self::$prototype));
        $this->assertSame('endpoint', (string) $route->forward(new FakeServerRequest(), self::$prototype)->getBody());
    }

    public function testForwardingRequest_ReturnsFirstMatchingEndpointResponse()
    {
        $route = $this->route(['A' => MockedRoute::response('first'), 'B' => MockedRoute::response('second')]);
        $this->assertSame('first', $route->forward(new FakeServerRequest(), self::$prototype)->body);
    }

    public function testUriMethod_ThrowsException()
    {
        $this->expectException(EndpointCallException::class);
        $this->route()->uri(new FakeUri(), []);
    }

    public function testSelectEndpointCall_ReturnsFoundRoute()
    {
        $route = $this->route([
            'A' => $routeA = MockedRoute::response('A'),
            'B' => $routeB = MockedRoute::response('B')
        ]);
        $this->assertSame($routeA, $route->select('A'));
        $this->assertSame($routeB, $route->select('B'));
    }

    public function testSelectSwitchCallWithMorePathSegments_AsksNextSwitch()
    {
        $route = $this->route([
            'AFound' => $routeA = MockedRoute::response('A'),
            'BFound' => $routeB = MockedRoute::response('B')
        ]);
        $selected = $route->select('AFound.PathA');
        $this->assertSame('PathA', $selected->path);
        $this->assertSame('A', $selected->response->body);

        $selected = $route->select('BFound.PathB.PathC');
        $this->assertSame('PathB.PathC', $selected->path);
        $this->assertSame('B', $selected->response->body);
    }

    public function testSelectWithEmptyPath_ThrowsException()
    {
        $this->expectException(SwitchCallException::class);
        $this->route()->select('');
    }

    public function testSelectWithUnknownPathName_ThrowsException()
    {
        $this->assertInstanceOf(Route::class, $this->route()->select('example'));
        $this->expectException(SwitchCallException::class);
        $this->route()->select('NotDefined');
    }

    public function testDefaultRouteIsScannedFirst()
    {
        $response = new FakeResponse();
        $subRoute = new MockedRoute($response);
        $router   = $this->route(['dummy' => MockedRoute::response('dummy')], $this->route(['nested' => $subRoute]));
        $this->assertSame($response, $router->forward(new FakeServerRequest(), new FakeResponse()));
    }

    public function testSelectUnknownPathWhenDefaultRoutePresent_SelectsPathFromDefaultRoute()
    {
        $subRoute = new MockedRoute();
        $router   = $this->route([], $this->route(['nested' => $subRoute]));
        $this->assertSame($subRoute, $router->select('nested'));
    }

    public function testSelectDefinedPathWhenDefaultRoutePresent_SelectsRouteForDefinedPath()
    {
        $router = $this->route(['route' => MockedRoute::response('defined')], $this->route(['route' => MockedRoute::response('default')]));
        $this->assertSame('defined', (string) $router->select('route')->forward(new FakeServerRequest(), new FakeResponse())->getBody());
    }

    public function testUriIsCalledFromDefaultRoute()
    {
        $router = $this->route([], MockedRoute::withUri('/foo/bar'));
        $this->assertSame('http://example.com/foo/bar', (string) $router->uri(FakeUri::fromString('http://example.com'), []));
    }

    private function route(array $routes = [], Route $default = null)
    {
        $dummy = new MockedRoute();
        return $default
            ? new ResponseScanSwitch(['example' => $dummy] + $routes, $default)
            : new ResponseScanSwitch(['example' => $dummy] + $routes);
    }
}
