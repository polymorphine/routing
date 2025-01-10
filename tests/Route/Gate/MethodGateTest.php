<?php declare(strict_types=1);

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
use Polymorphine\Routing\Map;
use Polymorphine\Routing\Tests\Doubles;


class MethodGateTest extends TestCase
{
    public function test_Instantiation()
    {
        $this->assertInstanceOf(Route::class, $this->gate());
    }

    public function test_Forward_NotMatchedGateMethodRequest_ReturnsPrototypeInstance()
    {
        $gate      = $this->gate('DELETE');
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $gate->forward(new Doubles\FakeServerRequest('POST'), $prototype));
    }

    public function test_Forward_MatchingGateMethodRequest_ReturnsRouteResponse()
    {
        $request  = new Doubles\FakeServerRequest('POST');
        $response = $this->gate('POST', $route)->forward($request, new Doubles\FakeResponse());
        $this->assertSame($response, $route->response);
    }

    public function test_Forward_NotMatchingAnyOfGateMethodsRequest_ReturnsPrototypeInstance()
    {
        $gate      = $this->gate('GET|POST|PUT');
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $gate->forward(new Doubles\FakeServerRequest('PATCH'), $prototype));
    }

    public function test_Forward_MatchingOneOfGateMethodsRequest_ReturnsRouteResponse()
    {
        $request  = new Doubles\FakeServerRequest('PUT');
        $response = $this->gate('POST|PUT|PATCH', $route)->forward($request, new Doubles\FakeResponse());
        $this->assertSame($response, $route->response);
    }

    public function test_Select_IsPassedDirectlyToNextRoute()
    {
        $selected = $this->gate('GET', $route)->select('some.name');
        $this->assertSame('some.name', $route->path);
        $this->assertSame($selected, $route->subRoute);
    }

    public function test_Uri_IsPassedDirectlyToNextRoute()
    {
        $uri = $this->gate('GET', $route)->uri(new Doubles\FakeUri(), []);
        $this->assertSame($uri, $route->uri);
    }

    public function test_WhenAnyOfTestedMethodsIsAllowed_ForwardedOptionsRequest_ReturnsResponseWithAllowedMethods()
    {
        $methodsAllowed = 'PATCH|PUT|DELETE';
        $methodsTested  = ['GET', 'POST', 'PATCH', 'PUT'];

        $endpoint = new Doubles\DummyEndpoint();
        $response = $this->gate($methodsAllowed, $endpoint)
                         ->forward($this->optionsRequest($methodsTested), new Doubles\FakeResponse());
        $this->assertSame(['PATCH, PUT'], $response->getHeader('Allow'));
    }

    public function test_WhenNoneOfTestedMethodsIsAllowed_ForwardedOptionRequest_ReturnsPrototypeResponse()
    {
        $methodsAllowed = 'PATCH|PUT|DELETE';
        $methodsTested  = ['GET', 'POST'];

        $endpoint = new Doubles\DummyEndpoint();
        $response = $this->gate($methodsAllowed, $endpoint)
                         ->forward($this->optionsRequest($methodsTested), $prototype = new Doubles\FakeResponse());
        $this->assertSame($prototype, $response);
    }

    public function test_WhenOptionsRouteIsDefined_ForwardedOptionsRequest_ReturnsStandardEndpointResponse()
    {
        $methodsAllowed = 'PATCH|PUT|OPTIONS';
        $methodsTested  = ['PATCH', 'PUT'];

        $response = $this->gate($methodsAllowed, $route)
                         ->forward($this->optionsRequest($methodsTested), new Doubles\FakeResponse());
        $this->assertSame($response, $route->response);
    }

    public function test_Routes_PassesTraceToNextRoute()
    {
        $trace  = new Map\Trace(new Map(), new Doubles\FakeUri());
        $method = 'GET';
        $this->gate($method, $route)->routes($trace);
        $this->assertEquals($trace->withMethod($method), $route->trace);
    }

    private function gate(string $methods = 'GET', ?Route &$route = null)
    {
        $route = $route ?? new Doubles\MockedRoute(new Doubles\FakeResponse(), new Doubles\FakeUri());
        return new Route\Gate\MethodGate($methods, $route);
    }

    private function optionsRequest(array $methodsTested)
    {
        $request = new Doubles\FakeServerRequest('OPTIONS');
        return $request->withAttribute(Route::METHODS_ATTRIBUTE, $methodsTested);
    }
}
