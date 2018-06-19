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
use Polymorphine\Routing\Route\Gate\PathSegmentGate;
use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class PathSegmentSwitchTest extends TestCase
{
    //TODO: Conflict detection with path gates

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, new PathSegmentSwitch([]));
    }

    public function testWhenNoRootRoute_UriMethodCall_ThrowsException()
    {
        $route = new PathSegmentSwitch([]);
        $this->expectException(EndpointCallException::class);
        $route->uri(new FakeUri(), []);
    }

    public function testWithRootRoute_UriMethodCall_ReturnsRootUri()
    {
        $route = new PathSegmentSwitch([], new MockedRoute('root'));
        $this->assertSame('root', $route->uri(new FakeUri(), [])->getPath());
    }

    public function testUriFromSelectedRootRoute_ReturnsRootProducedUri()
    {
        $route = new PathSegmentSwitch([], new MockedRoute('root'));
        $uri   = $route->uri(new FakeUri(), []);
        $this->assertEquals($uri, $route->select(PathSegmentSwitch::ROOT_PATH)->uri(new FakeUri(), []));
    }

    public function testWithRootRoute_UriOrForward_ReturnsResultsEquivalentToRootRouteCalls()
    {
        $route     = new PathSegmentSwitch([], new MockedRoute('root'));
        $structure = $this->createStructure($route, ['foo', 'bar']);
        $wrapped   = new PathSegmentGate('foo', new PathSegmentGate('bar', $route));
        $this->assertEquals($wrapped, $implicit = $structure->select('foo.bar'));
        $this->assertNotEquals($implicit, $explicit = $structure->select('foo.bar.' . PathSegmentSwitch::ROOT_PATH));

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('/foo/bar'));
        $this->assertEquals('root', (string) $implicit->forward($request, $prototype)->getBody());
        $this->assertEquals($implicit->forward($request, $prototype), $explicit->forward($request, $prototype));
        $this->assertEquals($implicit->uri(new FakeUri(), []), $explicit->uri(new FakeUri(), []));
    }

    public function testForwardNotMatchingPathSegment_ReturnsPrototypeInstance()
    {
        $route     = new PathSegmentSwitch([]);
        $prototype = new FakeResponse();
        $this->assertSame($prototype, $route->forward(new FakeServerRequest(), $prototype));
    }

    public function testWhenNoRootRoute_ForwardNotExistingPathSegment_ReturnsPrototypeInstance()
    {
        $route     = new PathSegmentSwitch([]);
        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('//domain.com/'));
        $this->assertSame($prototype, $route->forward($request, $prototype));
    }

    public function testWithRootRoute_ForwardNotExistingPathSegment_ReturnsRootResponse()
    {
        $route     = new PathSegmentSwitch([], new MockedRoute('root'));
        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('//domain.com/'));
        $this->assertSame('root', (string) $route->forward($request, $prototype)->getBody());
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
        $route = new PathSegmentSwitch([
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

    public function testSelectEndpointCall_ReturnsMatchingRoute()
    {
        $route = new PathSegmentSwitch([
            'A' => new MockedRoute('responseA'),
            'B' => new MockedRoute('responseB')
        ]);
        $this->assertSame('responseA', $this->routeForwardCall($route->select('A'), 'http://example.com/A/foo'));
        $this->assertSame('responseB', $this->routeForwardCall($route->select('B'), '/B/FizzBuzz'));
        $this->assertSame('prototype', $this->routeForwardCall($route));
    }

    public function testSelectNestedPathWithRoutePath_ReturnsSameRouteAsRepeatedRouteCall()
    {
        $route = $this->createStructure(new MockedRoute('endpoint'), ['foo', 'bar', 'baz']);
        $this->assertEquals($route->select('foo.bar.baz'), $route->select('foo')->select('bar')->select('baz'));
    }

    public function testSelectNestedRouteWithRoutePath_ReturnsRouteThatMatchesAllPathSegments()
    {
        $route = new PathSegmentSwitch([
            'A' => new PathSegmentSwitch([
                'A' => new MockedRoute('responseAA'),
                'B' => new MockedRoute('responseAB')
            ]),
            'B' => new MockedRoute('responseB')
        ]);
        $this->assertSame('prototype', $this->routeForwardCall($route->select('A'), 'http://example.com/B/A/123'));
        $this->assertSame('responseB', $this->routeForwardCall($route->select('B'), 'http://example.com/B/A/123'));
        $this->assertSame('responseAA', $this->routeForwardCall($route->select('A.A'), 'http://example.com/A/A/123'));
        $this->assertSame('responseAB', $this->routeForwardCall($route->select('A.B'), 'A/B/foo/bar'));
    }

    /**
     * @dataProvider segmentCombinations
     *
     * @param array  $segments
     * @param string $uri
     */
    public function testEndpointUri_ReturnsUriThatCanReachEndpoint(array $segments, string $uri)
    {
        $prototype = FakeUri::fromString($uri);
        $expected  = $prototype->withPath($prototype->getPath() . '/' . implode('/', $segments));

        $path     = implode(Route::PATH_SEPARATOR, $segments);
        $endpoint = new MockedRoute(); //need empty to return clean uri prototype
        $route    = $this->createStructure($endpoint, $segments);
        $this->assertSame((string) $expected, (string) $route->select($path)->uri($prototype, []));

        $endpoint->id = 'valid'; //need value for concrete response
        $request      = new FakeServerRequest('GET', $expected);
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
