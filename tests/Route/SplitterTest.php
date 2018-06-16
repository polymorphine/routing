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
        return new DummySplitter(['example' => $dummy] + $routes);
    }
}
