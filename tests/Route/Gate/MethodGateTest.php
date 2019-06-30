<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Gate;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Tests;
use Polymorphine\Routing\Tests\Doubles;


class MethodGateTest extends TestCase
{
    use Tests\RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->gate());
    }

    public function testNotMatchingGateMethodRequestForward_ReturnsPrototypeInstance()
    {
        $route = $this->gate('DELETE');
        $this->assertSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('POST'), self::$prototype));
    }

    public function testMatchingGateMethodRequestForward_ReturnsRouteResponse()
    {
        $route = $this->gate('POST');
        $this->assertNotSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('POST'), self::$prototype));
    }

    public function testNotMatchingAnyOfGateMethodsRequestForward_ReturnsPrototypeInstance()
    {
        $route = $this->gate('GET|POST|PUT');
        $this->assertSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('PATCH'), self::$prototype));
    }

    public function testMatchingOneOfGateMethodsRequestForward_ReturnsRouteResponse()
    {
        $route = $this->gate('POST|PUT|PATCH|DELETE');
        $this->assertNotSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('PUT'), self::$prototype));
    }

    public function testSelectCallIsPassedDirectlyToNextRoute()
    {
        $route = $this->gate('GET', $next = Doubles\MockedRoute::response('next'));
        $this->assertSame($next, $route->select('some.path'));
        $this->assertSame('some.path', $next->path);
    }

    public function testUriCallIsPassedDirectlyToNextRoute()
    {
        $route = $this->gate('GET', Doubles\MockedRoute::withUri('next'));
        $this->assertSame('next', $route->uri(new Doubles\FakeUri(), [])->getPath());
    }

    public function testWhenAnyOfTestedMethodsIsAllowed_ForwardedOptionsRequest_ReturnsResponseWithAllowedMethods()
    {
        $methodsAllowed = 'PATCH|PUT|DELETE';
        $methodsTested  = ['GET', 'POST', 'PATCH', 'PUT'];

        $request = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $route   = $this->gate($methodsAllowed, new Doubles\DummyEndpoint());
        $this->assertSame(['PATCH, PUT'], $route->forward($request, new Doubles\FakeResponse())->getHeader('Allow'));
    }

    public function testWhenNoneOfTestedMethodsIsAllowed_ForwardedOptionRequest_ReturnsPrototypeResponse()
    {
        $methodsAllowed = 'PATCH|PUT|DELETE';
        $methodsTested  = ['GET', 'POST'];

        $request   = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $prototype = new Doubles\FakeResponse();
        $route     = $this->gate($methodsAllowed, new Doubles\DummyEndpoint());
        $this->assertSame($prototype, $route->forward($request, $prototype));
    }

    public function testWhenOptionsRouteIsDefined_ForwardedOptionsRequest_ReturnsStandardEndpointResponse()
    {
        $methodsAllowed = 'PATCH|PUT|OPTIONS';
        $methodsTested  = ['PATCH', 'PUT'];

        $request  = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
        $response = new Doubles\FakeResponse();
        $route    = $this->gate($methodsAllowed, new Doubles\MockedRoute($response));
        $this->assertSame($response, $route->forward($request, new Doubles\FakeResponse()));
    }

    private function gate(string $methods = 'GET', Route $route = null)
    {
        return new Route\Gate\MethodGate($methods, $route ?? Doubles\MockedRoute::response('forwarded'));
    }
}
