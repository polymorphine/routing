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
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class MethodSwitchTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(MethodSwitch::class, $this->splitter());
    }

    public function testRequestMatchingMethod_ReturnsResponseFromMatchedRoute()
    {
        $splitter = $this->splitter();
        $methods  = ['POST', 'GET', 'PUT', 'PATCH', 'DELETE'];
        foreach ($methods as $method) {
            $request = new FakeServerRequest($method);
            $this->assertSame($method, (string) $splitter->forward($request, new FakeResponse())->getBody());
        }
    }

    public function testRequestNotMatchingMethod_ReturnsPrototypeResponse()
    {
        $methods = ['POST', 'GET', 'PUT', 'PATCH', 'DELETE'];
        foreach ($methods as $idx => $method) {
            $defined = $methods;
            unset($defined[$idx]);
            $splitter  = $this->splitter($defined);
            $prototype = new FakeResponse();
            $this->assertSame($prototype, $splitter->forward(new FakeServerRequest($method), $prototype), $method);
        }
    }

    public function testSelectMatchingRouteWithMethodName_ReturnsRouteForThisMethod()
    {
        $splitter = new MethodSwitch([
            'POST'   => $routePost = new MockedRoute(),
            'DELETE' => $routeDelete = new MockedRoute()
        ]);
        $route = $splitter->select('POST');
        $this->assertSame($routePost, $route);
        $this->assertNull($routePost->path);

        $route = $splitter->select('DELETE');
        $this->assertSame($routeDelete, $route);
        $this->assertNull($routeDelete->path);
    }

    public function testSelectMatchingRouteWithPath_ReturnsRouteFromNextSwitches()
    {
        $splitter = new MethodSwitch([
            'GET' => $routeGet = new MockedRoute(),
            'PUT' => $routePut = new MockedRoute()
        ]);
        $route = $splitter->select('GET.next.switch');
        $this->assertSame($routeGet, $route);
        $this->assertSame('next.switch', $routeGet->path);

        $route = $splitter->select('PUT.path.after.put');
        $this->assertSame($routePut, $route);
        $this->assertSame('path.after.put', $routePut->path);
    }

    public function testSelectMatchingRouteWithImplicitPath_ReturnsRouteFromNextSwitches()
    {
        $splitter = new MethodSwitch([
            'GET' => $routeGet = new MockedRoute(),
            'PUT' => $routePut = new MockedRoute()
        ], 'GET');
        $route = $splitter->select('implicit.path');
        $this->assertSame($routeGet, $route);
        $this->assertSame('implicit.path', $routeGet->path);

        $route = $splitter->select('PUT.explicit.path');
        $this->assertSame($routePut, $route);
        $this->assertSame('explicit.path', $routePut->path);
    }

    public function testUriMethodWithoutImplicitMethod_ThrowsException()
    {
        $splitter = new MethodSwitch([
            'GET' => MockedRoute::withUri('get'),
            'POST' => MockedRoute::withUri('post')
        ]);
        $this->expectException(EndpointCallException::class);
        $splitter->uri(new FakeUri(), []);
    }

    public function testUriMethodWithImplicitMethod_ForwardsCallToImplicitRoute()
    {
        $splitter = new MethodSwitch([
            'GET' => MockedRoute::withUri('get/implicit'),
            'POST' => MockedRoute::withUri('post')
        ], 'GET');
        $this->assertSame('get/implicit', (string) $splitter->uri(new FakeUri(), []));
    }

    private function splitter(array $methods = ['POST', 'GET', 'PUT', 'PATCH', 'DELETE']): MethodSwitch
    {
        $routes = [];
        foreach ($methods as $method) {
            $routes[$method] = new MockedRoute(new FakeResponse($method), new FakeUri());
        }

        return new MethodSwitch($routes);
    }
}
