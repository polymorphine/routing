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
use Psr\Http\Message\UriInterface;


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

        $this->assertSame('prototype', $this->routeForwardCall($route));
        $this->assertSame('responseAB', $this->routeForwardCall($route, '/A/B/C/D'));
        $this->assertSame('responseBA', $this->routeForwardCall($route, 'B/A/foo/bar'));
    }

    public function testRouteMethodEndpointCall_ReturnsMatchingRoute()
    {
        $route  = new PathSegmentSwitch([
            'A' => new MockedRoute('responseA'),
            'B' => new MockedRoute('responseB')
        ]);
        $this->assertSame('responseA', $this->routeForwardCall($route->route('A'), 'http://example.com/A/foo'));
        $this->assertSame('responseB', $this->routeForwardCall($route->route('B'), '/B/FizzBuzz'));
        $this->assertSame('prototype', $this->routeForwardCall($route));
    }

    public function testNestedPathWithRoutePath_ReturnsSameRouteAsRepeatedRouteCall()
    {
        $route = $this->createStructure(new MockedRoute('endpoint'), ['foo', 'bar', 'baz']);
        $this->assertEquals($route->route('foo.bar.baz'), $route->route('foo')->route('bar')->route('baz'));
    }

    public function testAccessNestedRouteWithRoutePath_ReturnsRouteThatMatchesAllPathSegments()
    {
        $route  = new PathSegmentSwitch([
            'A' => new PathSegmentSwitch([
                'A' => new MockedRoute('responseAA'),
                'B' => new MockedRoute('responseAB')
            ]),
            'B' => new MockedRoute('responseB')
        ]);
        $this->assertSame('prototype', $this->routeForwardCall($route->route('A'), 'http://example.com/B/A/123'));
        $this->assertSame('responseB', $this->routeForwardCall($route->route('B'), 'http://example.com/B/A/123'));
        $this->assertSame('responseAA', $this->routeForwardCall($route->route('A.A'), 'http://example.com/A/A/123'));
        $this->assertSame('responseAB', $this->routeForwardCall($route->route('A.B'), 'A/B/foo/bar'));
    }

    /**
     * @dataProvider segmentCombinations
     * @param array $segments
     * @param string $uri
     */
    public function testEndpointUri_ReturnsUriThatCanReachEndpoint(array $segments, string $uri)
    {
        $prototype = FakeUri::fromString($uri);
        $expected  = $prototype->withPath($prototype->getPath() . '/' . implode('/', $segments));

        $path     = implode(Route::PATH_SEPARATOR, $segments);
        $endpoint = new MockedRoute(); //need empty to return clean uri prototype
        $route    = $this->createStructure($endpoint, $segments);
        $this->assertSame((string) $expected, (string) $route->route($path)->uri($prototype, []));

        $endpoint->id = 'valid'; //need value for concrete response
        $request = new FakeServerRequest('GET', $expected);
        $this->assertSame('valid', (string) $route->forward($request, new FakeResponse('prototype'))->getBody());
    }

    public function segmentCombinations()
    {
        return [
            [['foo', 'bar'], 'http://example.com?query=string'],
            [['foo', 'bar', 'baz'], 'http://example.com?query=string'],
            [['foo'], 'http:?query']
        ];
    }

    private function routeForwardCall(Route $route, string $requestUri = null): string
    {
        $response = $route->forward(
            new FakeServerRequest('GET', $requestUri ? FakeUri::fromString($requestUri) : new FakeUri()),
            new FakeResponse('prototype')
        );
        return (string) $response->getBody();
    }

    private function createStructure(Route $endpoint, array $segments)
    {
        $route = $endpoint;
        while ($segment = array_pop($segments)) {
            $route = new PathSegmentSwitch([$segment => $route]);
        }
        return $route;
    }
}
