<?php

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
    private function route(array $routes = [])
    {
        $dummy = new MockedRoute('DUMMY', function () { return null; });
        return new DummySplitter(['example' => $dummy] + $routes);
    }

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
        $this->assertSame('A', $route->route('A')->id);
        $this->assertSame('B', $route->route('B')->id);
    }

    public function testRouteMethodSwitchCall_AsksNextSwitch()
    {
        $routeA = new MockedRoute('A');
        $routeB = new MockedRoute('B');
        $route  = $this->route(['AFound' => $routeA, 'BFound' => $routeB]);
        $this->assertSame('PathA', $route->route('AFound.PathA')->path);
        $this->assertSame('PathB', $route->route('BFound.PathB')->path);
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
}
