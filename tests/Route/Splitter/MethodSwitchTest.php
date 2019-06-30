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
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use Polymorphine\Routing\Tests\Doubles;


class MethodSwitchTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->splitter());
    }

    public function testRequestMatchingMethod_ReturnsResponseFromMatchedRoute()
    {
        $splitter = new MethodSwitch([
            'POST'   => $this->responseRoute($post),
            'GET'    => $this->responseRoute($get),
            'DELETE' => $this->responseRoute($delete),
            'PUT'    => $this->responseRoute($put),
            'PATCH'  => $this->responseRoute($patch)
        ]);

        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($post, $splitter->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($get, $splitter->forward($request->withMethod('GET'), $prototype));
        $this->assertSame($delete, $splitter->forward($request->withMethod('DELETE'), $prototype));
        $this->assertSame($put, $splitter->forward($request->withMethod('PUT'), $prototype));
        $this->assertSame($patch, $splitter->forward($request->withMethod('PATCH'), $prototype));
    }

    public function testRequestNotMatchingMethod_ReturnsPrototypeResponse()
    {
        $splitter  = $this->splitter(['POST', 'GET', 'PATCH', 'DELETE']);
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $splitter->forward(new Doubles\FakeServerRequest('PUT'), $prototype));
    }

    public function testSelectMatchingRouteWithMethodName_ReturnsRouteForThisMethod()
    {
        $splitter = new MethodSwitch([
            'POST'   => $routePost = new Doubles\MockedRoute(),
            'DELETE' => $routeDelete = new Doubles\MockedRoute()
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
            'GET' => $routeGet = new Doubles\MockedRoute(),
            'PUT' => $routePut = new Doubles\MockedRoute()
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
            'GET' => $routeGet = new Doubles\MockedRoute(),
            'PUT' => $routePut = new Doubles\MockedRoute()
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
            'GET'  => Doubles\MockedRoute::withUri('get'),
            'POST' => Doubles\MockedRoute::withUri('post')
        ], null);
        $this->expectException(EndpointCallException::class);
        $splitter->uri(new Doubles\FakeUri(), []);
    }

    public function testUriMethodWithImplicitMethod_ForwardsCallToImplicitRoute()
    {
        $splitter = new MethodSwitch([
            'GET'  => Doubles\MockedRoute::withUri('get/implicit'),
            'POST' => Doubles\MockedRoute::withUri('post')
        ], 'GET');
        $this->assertSame('get/implicit', (string) $splitter->uri(new Doubles\FakeUri(), []));
    }

    public function testWhenAnyOfTestedMethodsIsAllowed_ForwardedOptionsRequest_ReturnsResponseWithAllowedMethods()
    {
        $methodsAllowed = ['GET', 'POST', 'PUT', 'DELETE'];
        $methodsTested  = ['GET', 'PUT', 'DELETE', 'PATCH'];

        $splitter = new MethodSwitch(array_fill_keys($methodsAllowed, new Doubles\DummyEndpoint()));
        $request  = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $this->assertSame(['GET, PUT, DELETE'], $splitter->forward($request, new Doubles\FakeResponse())->getHeader('Allow'));
    }

    public function testWhenNoneOfTestedMethodsIsAllowed_ForwardedOptionRequest_ReturnsPrototypeResponse()
    {
        $methodsAllowed = ['POST', 'PATCH'];
        $methodsTested  = ['GET', 'PUT', 'DELETE'];

        $splitter  = new MethodSwitch(array_fill_keys($methodsAllowed, new Doubles\DummyEndpoint()));
        $request   = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $splitter->forward($request, $prototype));
    }

    public function testWhenOptionsRouteIsDefined_ForwardedOptionsRequest_ReturnsStandardEndpointResponse()
    {
        $methodsAllowed = ['GET', 'POST', 'OPTIONS', 'DELETE'];
        $methodsTested  = ['GET', 'PUT', 'DELETE', 'PATCH'];

        $response = new Doubles\FakeResponse();
        $splitter = new MethodSwitch(array_fill_keys($methodsAllowed, new Doubles\MockedRoute($response)));
        $request  = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $this->assertSame($response, $splitter->forward($request, new Doubles\FakeResponse()));
    }

    private function splitter(array $methods = ['POST', 'GET', 'PUT', 'PATCH', 'DELETE']): MethodSwitch
    {
        $routes = [];
        foreach ($methods as $method) {
            $routes[$method] = new Doubles\MockedRoute(new Doubles\FakeResponse($method));
        }

        return new MethodSwitch($routes);
    }
}
