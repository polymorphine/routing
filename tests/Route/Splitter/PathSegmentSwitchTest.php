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
use Polymorphine\Routing\Route\Splitter\PathSegmentSwitch;
use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;


class PathSegmentSwitchTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, new PathSegmentSwitch([]));
    }

    public function testUriMethodCall_ThrowsException()
    {
        $route = new PathSegmentSwitch([]);
        $this->expectException(EndpointCallException::class);
        $route->uri(new FakeUri(), []);
    }

    public function testForwardNotMatchingPathSegment_ReturnsPrototypeInstance()
    {
        $route     = new PathSegmentSwitch([]);
        $prototype = new FakeResponse();
        $this->assertSame($prototype, $route->forward(new FakeServerRequest(), $prototype));
    }

    public function testForwardMatchingPathSegment_ReturnsRouteResponse()
    {
        $route     = new PathSegmentSwitch(['foo' => new MockedRoute('response.body')]);
        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('/foo/bar'));
        $response  = $route->forward($request, $prototype);
        $this->assertNotSame($prototype, $response);
        $this->assertSame('response.body', (string) $response->getBody());
    }

    public function testNestedSwitchForwardMatchingRequest_ReturnsEndpointRouteResponse()
    {
        $route  = new PathSegmentSwitch([
            'A' => new PathSegmentSwitch([
                'A' => new MockedRoute('responseAA'),
                'B' => new MockedRoute('responseAB')
            ]),
            'B' => new PathSegmentSwitch([
                'A' => new MockedRoute('responseBA'),
                'B' => new MockedRoute('responseBB')
            ])
        ]);

        $requestAB = new FakeServerRequest('GET', FakeUri::fromString('/A/B/C/D'));
        $requestBA = new FakeServerRequest('GET', FakeUri::fromString('B/A/foo/bar'));

        $this->assertSame('prototype', $this->routeForwardCall($route));
        $this->assertSame('responseAB', $this->routeForwardCall($route, $requestAB));
        $this->assertSame('responseBA', $this->routeForwardCall($route, $requestBA));
    }

    public function testRouteMethodEndpointCall_ReturnsMatchingRoute()
    {
        $route  = new PathSegmentSwitch([
            'A' => new MockedRoute('responseA'),
            'B' => new MockedRoute('responseB')
        ]);
        $this->assertSame('responseA', $this->routeForwardCall($route->route('A')));
        $this->assertSame('responseB', $this->routeForwardCall($route->route('B')));
        $this->assertSame('prototype', $this->routeForwardCall($route));
    }

    public function testAccessNestedRouteWithRoutePath()
    {
        $route  = new PathSegmentSwitch([
            'A' => new PathSegmentSwitch([
                'A' => new MockedRoute('responseAA'),
                'B' => new MockedRoute('responseAB')
            ]),
            'B' => new MockedRoute('responseB')
        ]);
        $this->assertSame('prototype', $this->routeForwardCall($route->route('A')));
        $this->assertSame('responseB', $this->routeForwardCall($route->route('B')));
        $this->assertSame('responseAA', $this->routeForwardCall($route->route('A.A')));
        $this->assertSame('responseAB', $this->routeForwardCall($route->route('A.B')));
    }

    private function routeForwardCall(Route $route, ServerRequestInterface $request = null): string
    {
        return (string) $route->forward($request ?? new FakeServerRequest(), new FakeResponse('prototype'))->getBody();
    }
}
