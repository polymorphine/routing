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
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\DummyEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


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

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $this->assertSame($post, $splitter->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($get, $splitter->forward($request->withMethod('GET'), $prototype));
        $this->assertSame($delete, $splitter->forward($request->withMethod('DELETE'), $prototype));
        $this->assertSame($put, $splitter->forward($request->withMethod('PUT'), $prototype));
        $this->assertSame($patch, $splitter->forward($request->withMethod('PATCH'), $prototype));
    }

    public function testRequestNotMatchingMethod_ReturnsPrototypeResponse()
    {
        $splitter  = $this->splitter(['POST', 'GET', 'PATCH', 'DELETE']);
        $prototype = new FakeResponse();
        $this->assertSame($prototype, $splitter->forward(new FakeServerRequest('PUT'), $prototype));
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
            'GET'  => MockedRoute::withUri('get'),
            'POST' => MockedRoute::withUri('post')
        ], null);
        $this->expectException(EndpointCallException::class);
        $splitter->uri(new FakeUri(), []);
    }

    public function testUriMethodWithImplicitMethod_ForwardsCallToImplicitRoute()
    {
        $splitter = new MethodSwitch([
            'GET'  => MockedRoute::withUri('get/implicit'),
            'POST' => MockedRoute::withUri('post')
        ], 'GET');
        $this->assertSame('get/implicit', (string) $splitter->uri(new FakeUri(), []));
    }

    public function testWhenAnyOfTestedMethodsIsAllowed_ForwardedOptionsRequest_ReturnsResponseWithAllowedMethods()
    {
        $methodsAllowed = ['GET', 'POST', 'PUT', 'DELETE'];
        $methodsTested  = ['GET', 'PUT', 'DELETE', 'PATCH'];

        $splitter = new MethodSwitch(array_fill_keys($methodsAllowed, new DummyEndpoint()));
        $request  = (new FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $this->assertSame(['GET, PUT, DELETE'], $splitter->forward($request, new FakeResponse())->getHeader('Allow'));
    }

    public function testWhenNoneOfTestedMethodsIsAllowed_ForwardedOptionRequest_ReturnsPrototypeResponse()
    {
        $methodsAllowed = ['POST', 'PATCH'];
        $methodsTested  = ['GET', 'PUT', 'DELETE'];

        $splitter  = new MethodSwitch(array_fill_keys($methodsAllowed, new DummyEndpoint()));
        $request   = (new FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $prototype = new FakeResponse();
        $this->assertSame($prototype, $splitter->forward($request, $prototype));
    }

    public function testWhenOptionsRouteIsDefined_ForwardedOptionsRequest_ReturnsStandardEndpointResponse()
    {
        $methodsAllowed = ['GET', 'POST', 'OPTIONS', 'DELETE'];
        $methodsTested  = ['GET', 'PUT', 'DELETE', 'PATCH'];

        $response = new FakeResponse();
        $splitter = new MethodSwitch(array_fill_keys($methodsAllowed, new MockedRoute($response)));
        $request  = (new FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $this->assertSame($response, $splitter->forward($request, new FakeResponse()));
    }

    private function splitter(array $methods = ['POST', 'GET', 'PUT', 'PATCH', 'DELETE']): MethodSwitch
    {
        $routes = [];
        foreach ($methods as $method) {
            $routes[$method] = new MockedRoute(new FakeResponse($method));
        }

        return new MethodSwitch($routes);
    }
}
