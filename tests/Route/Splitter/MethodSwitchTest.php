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
use Polymorphine\Routing\Map;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;
use Polymorphine\Routing\Tests\RoutingTestMethods;


class MethodSwitchTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->splitter());
    }

    public function testRequestMatchingMethod_ReturnsResponseFromMatchedRoute()
    {
        $splitter = new Route\Splitter\MethodSwitch([
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
        $splitter = new Route\Splitter\MethodSwitch($routes = [
            'POST'   => new Doubles\MockedRoute(),
            'DELETE' => new Doubles\MockedRoute()
        ]);
        $route = $splitter->select('POST');
        $this->assertSame($route, $routes['POST']);
        $this->assertNull($routes['POST']->path);

        $route = $splitter->select('DELETE');
        $this->assertSame($route, $routes['DELETE']);
        $this->assertNull($routes['DELETE']->path);
    }

    public function testSelectMatchingRouteWithPath_ReturnsRouteFromNextSwitches()
    {
        $splitter = new Route\Splitter\MethodSwitch($routes = [
            'GET' => new Doubles\MockedRoute(),
            'PUT' => new Doubles\MockedRoute()
        ]);
        $route = $splitter->select('GET.next.switch');
        $this->assertSame($route, $routes['GET']->subRoute);
        $this->assertSame('next.switch', $routes['GET']->path);

        $route = $splitter->select('PUT.path.after.put');
        $this->assertSame($route, $routes['PUT']->subRoute);
        $this->assertSame('path.after.put', $routes['PUT']->path);
    }

    public function testSelectMatchingRouteWithImplicitPath_ReturnsRouteFromNextSwitches()
    {
        $splitter = new Route\Splitter\MethodSwitch($routes = [
            'GET' => $routeGet = new Doubles\MockedRoute(),
            'PUT' => $routePut = new Doubles\MockedRoute()
        ], 'GET');
        $route = $splitter->select('implicit.path');
        $this->assertSame($route, $routes['GET']->subRoute);
        $this->assertSame('implicit.path', $routes['GET']->path);

        $route = $splitter->select('PUT.explicit.path');
        $this->assertSame($route, $routes['PUT']->subRoute);
        $this->assertSame('explicit.path', $routes['PUT']->path);
    }

    public function testUriMethodWithoutImplicitMethod_ThrowsException()
    {
        $splitter = new Route\Splitter\MethodSwitch([
            'GET'  => Doubles\MockedRoute::withUri('get'),
            'POST' => Doubles\MockedRoute::withUri('post')
        ], null);
        $this->expectException(Exception\UndefinedUriException::class);
        $splitter->uri(new Doubles\FakeUri(), []);
    }

    public function testUriMethodWithImplicitMethod_ForwardsCallToImplicitRoute()
    {
        $splitter = new Route\Splitter\MethodSwitch([
            'GET'  => Doubles\MockedRoute::withUri('get/implicit'),
            'POST' => Doubles\MockedRoute::withUri('post')
        ], 'GET');
        $this->assertSame('get/implicit', (string) $splitter->uri(new Doubles\FakeUri(), []));
    }

    public function testWhenAnyOfTestedMethodsIsAllowed_ForwardedOptionsRequest_ReturnsResponseWithAllowedMethods()
    {
        $methodsAllowed = ['GET', 'POST', 'PUT', 'DELETE'];
        $methodsTested  = ['GET', 'PUT', 'DELETE', 'PATCH'];

        $splitter = new Route\Splitter\MethodSwitch(array_fill_keys($methodsAllowed, new Doubles\DummyEndpoint()));
        $request  = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $this->assertSame(['GET, PUT, DELETE'], $splitter->forward($request, new Doubles\FakeResponse())->getHeader('Allow'));
    }

    public function testWhenNoneOfTestedMethodsIsAllowed_ForwardedOptionRequest_ReturnsPrototypeResponse()
    {
        $methodsAllowed = ['POST', 'PATCH'];
        $methodsTested  = ['GET', 'PUT', 'DELETE'];

        $splitter  = new Route\Splitter\MethodSwitch(array_fill_keys($methodsAllowed, new Doubles\DummyEndpoint()));
        $request   = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $splitter->forward($request, $prototype));
    }

    public function testWhenOptionsRouteIsDefined_ForwardedOptionsRequest_ReturnsStandardEndpointResponse()
    {
        $methodsAllowed = ['GET', 'POST', 'OPTIONS', 'DELETE'];
        $methodsTested  = ['GET', 'PUT', 'DELETE', 'PATCH'];

        $response = new Doubles\FakeResponse();
        $splitter = new Route\Splitter\MethodSwitch(array_fill_keys($methodsAllowed, new Doubles\MockedRoute($response)));
        $request  = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $this->assertSame($response, $splitter->forward($request, new Doubles\FakeResponse()));
    }

    public function testRoutesMethod_AddsRouteTracedPathsToRoutingMap()
    {
        $splitter = new Route\Splitter\MethodSwitch([
            'GET'  => new Doubles\MockedRoute(),
            'POST' => new Doubles\MockedRoute()
        ]);

        $map   = new Map();
        $uri   = '/foo/bar';
        $trace = (new Map\Trace($map, Doubles\FakeUri::fromString($uri)))->nextHop('path');

        $splitter->routes($trace);
        $expected = [
            new Map\Path('path', 'GET', $uri),
            new Map\Path('path.GET', 'GET', $uri),
            new Map\Path('path.POST', 'POST', $uri)
        ];

        $this->assertEquals($expected, $map->paths());
    }

    public function testImplicitRouteNameConflict_ThrowsException()
    {
        $splitter = new Route\Splitter\MethodSwitch([
            'GET' => Doubles\MockedRoute::withTraceCallback(function (Map\Trace $trace) {
                $trace->nextHop('POST');
            }),
            'POST' => new Doubles\MockedRoute()
        ]);

        $trace = new Map\Trace(new Map(), new Doubles\FakeUri());
        $this->expectException(Map\Exception\UnreachableEndpointException::class);
        $splitter->routes($trace);
    }

    private function splitter(array $methods = ['POST', 'GET', 'PUT', 'PATCH', 'DELETE']): Route\Splitter\MethodSwitch
    {
        $routes = [];
        foreach ($methods as $method) {
            $routes[$method] = new Doubles\MockedRoute(new Doubles\FakeResponse($method));
        }

        return new Route\Splitter\MethodSwitch($routes);
    }
}
