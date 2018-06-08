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
use Polymorphine\Routing\Route\FirstMatchForwardGateway;
use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Exception\GatewayCallException;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class FirstMatchForwardGatewayTest extends TestCase
{
    private static $notFound;

    public static function setUpBeforeClass()
    {
        self::$notFound = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testForwardingNotMatchingRequest_ReturnsNotFoundInstance()
    {
        $route = $this->route();
        $this->assertSame(self::$notFound, $route->forward(new FakeServerRequest(), self::$notFound));

        $route = $this->route(['name' => new MockedRoute('')]);
        $this->assertSame(self::$notFound, $route->forward(new FakeServerRequest(), self::$notFound));
    }

    public function testForwardingMatchingRequest_ReturnsEndpointResponse()
    {
        $route = new MockedRoute('', function () { return new FakeResponse(); });
        $route = $this->route(['name' => $route]);
        $this->assertNotSame(self::$notFound, $route->forward(new FakeServerRequest(), self::$notFound));
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
        $this->assertSame('A', $route->forward($requestA, self::$notFound)->body);
        $this->assertSame('B', $route->forward($requestB, self::$notFound)->body);
    }

    public function testUriMethod_ThrowsException()
    {
        $this->expectException(EndpointCallException::class);
        $this->route()->uri(new FakeUri(), []);
    }

    public function testGatewayMethodEndpointCall_ReturnsFoundRoute()
    {
        $routeA = new MockedRoute('A');
        $routeB = new MockedRoute('B');
        $route  = $this->route(['A' => $routeA, 'B' => $routeB]);
        $this->assertSame('A', $route->gateway('A')->id);
        $this->assertSame('B', $route->gateway('B')->id);
    }

    public function testGatewayMethodGatewayCall_AsksNextGateway()
    {
        $routeA = new MockedRoute('A');
        $routeB = new MockedRoute('B');
        $route  = $this->route(['AFound' => $routeA, 'BFound' => $routeB]);
        $this->assertSame('PathA', $route->gateway('AFound.PathA')->path);
        $this->assertSame('PathB', $route->gateway('BFound.PathB')->path);
    }

    public function testGatewayWithEmptyPath_ThrowsException()
    {
        $this->expectException(GatewayCallException::class);
        $this->route()->gateway('');
    }

    public function testGatewayWithUnknownName_ThrowsException()
    {
        $this->assertInstanceOf(Route::class, $this->route()->gateway('example'));
        $this->expectException(GatewayCallException::class);
        $this->route()->gateway('NotDefined');
    }

    private function route(array $routes = [])
    {
        $dummy           = new MockedRoute('DUMMY');
        $dummy->callback = function () { return null; };
        return new FirstMatchForwardGateway(['example' => $dummy] + $routes);
    }
}
