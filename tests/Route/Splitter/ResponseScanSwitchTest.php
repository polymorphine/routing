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
use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Exception\SwitchCallException;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Splitter\ResponseScanSwitch;
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

        $route = $this->route(['name' => new MockedRoute('')]);
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest(), self::$prototype));
    }

    public function testForwardingMatchingRequest_ReturnsEndpointResponse()
    {
        $route = new MockedRoute('', function () { return new FakeResponse(); });
        $route = $this->route(['name' => $route]);
        $this->assertNotSame(self::$prototype, $route->forward(new FakeServerRequest(), self::$prototype));
    }

    public function testForwardingMatchingRequest_ReturnsMatchingEndpointResponse()
    {
        $callback = function ($request) { return ($request->method === 'POST') ? new FakeResponse('A') : null; };
        $routeA   = new MockedRoute('', $callback);
        $callback = function ($request) { return ($request->method === 'GET') ? new FakeResponse('B') : null; };
        $routeB   = new MockedRoute('', $callback);
        $route    = $this->route(['A' => $routeA, 'B' => $routeB]);
        $requestA = new FakeServerRequest('POST');
        $requestB = new FakeServerRequest('GET');
        $this->assertSame('A', $route->forward($requestA, self::$prototype)->body);
        $this->assertSame('B', $route->forward($requestB, self::$prototype)->body);
    }

    public function testUriMethod_ThrowsException()
    {
        $this->expectException(EndpointCallException::class);
        $this->route()->uri(new FakeUri(), []);
    }

    public function testSelectEndpointCall_ReturnsFoundRoute()
    {
        $routeA = new MockedRoute('A');
        $routeB = new MockedRoute('B');
        $route  = $this->route(['A' => $routeA, 'B' => $routeB]);
        $this->assertSame($routeA, $route->select('A'));
        $this->assertSame($routeB, $route->select('B'));
    }

    public function testSelectSwitchCall_AsksNextSwitch()
    {
        $routeA = new MockedRoute('A');
        $routeB = new MockedRoute('B');
        $route  = $this->route(['AFound' => $routeA, 'BFound' => $routeB]);
        $this->assertSame('PathA', $route->select('AFound.PathA')->path);
        $this->assertSame('PathB.PathC', $route->select('BFound.PathB.PathC')->path);
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

    private function route(array $routes = [])
    {
        $dummy = new MockedRoute('DUMMY', function () { return null; });
        return new ResponseScanSwitch(['example' => $dummy] + $routes);
    }
}
