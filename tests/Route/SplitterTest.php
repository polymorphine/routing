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
use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Exception\SwitchCallException;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Splitter;
use Polymorphine\Routing\Tests\Doubles\DummySplitter;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;


class SplitterTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Splitter::class, $this->route());
    }

    public function testUriMethod_ThrowsException()
    {
        $this->expectException(EndpointCallException::class);
        $this->route()->uri(new FakeUri(), []);
    }

    public function testRouteMethodEndpointCall_ReturnsFoundRoute()
    {
        $routeA = new MockedRoute('A');
        $routeB = new MockedRoute('B');
        $route  = $this->route(['A' => $routeA, 'B' => $routeB]);
        $this->assertSame($routeA, $route->route('A'));
        $this->assertSame($routeB, $route->route('B'));
    }

    public function testRouteMethodSwitchCall_AsksNextSwitch()
    {
        $routeA = new MockedRoute('A');
        $routeB = new MockedRoute('B');
        $route  = $this->route(['AFound' => $routeA, 'BFound' => $routeB]);
        $this->assertSame('PathA', $route->route('AFound.PathA')->path);
        $this->assertSame('PathB.PathC', $route->route('BFound.PathB.PathC')->path);
    }

    public function testRouteCallWithEmptyPath_ThrowsException()
    {
        $this->expectException(SwitchCallException::class);
        $this->route()->route('');
    }

    public function testRouteCallWithUnknownName_ThrowsException()
    {
        $this->assertInstanceOf(Route::class, $this->route()->route('example'));
        $this->expectException(SwitchCallException::class);
        $this->route()->route('NotDefined');
    }

    private function route(array $routes = [])
    {
        $dummy = new MockedRoute('DUMMY', function () { return null; });
        return new DummySplitter(['example' => $dummy] + $routes);
    }
}
