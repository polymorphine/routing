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
use Psr\Http\Message\ResponseInterface;


class PathSwitchTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->splitter());
    }

    public function testForwardNotMatchingPathSegment_ReturnsPrototypeInstance()
    {
        $splitter = $this->splitter();
        $this->assertSame(self::$prototype, $splitter->forward(new Doubles\FakeServerRequest(), self::$prototype));
    }

    public function testForwardMatchingPathSegment_ReturnsRouteResponse()
    {
        $splitter = $this->splitter(['foo' => $this->responseRoute($foo)]);
        $request  = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/foo/bar'));
        $this->assertSame($foo, $splitter->forward($request, self::$prototype));
    }

    public function testWhenNoRootRoute_ForwardNotExistingPathSegment_ReturnsPrototypeInstance()
    {
        $splitter = $this->splitter();
        $request  = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/'));
        $this->assertSame(self::$prototype, $splitter->forward($request, self::$prototype));
    }

    public function testWithRootRoute_ForwardNotExistingPathSegment_ReturnsRootResponse()
    {
        $splitter = $this->splitter([], $this->responseRoute($response));
        $request  = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/'));
        $this->assertSame($response, $splitter->forward($request, self::$prototype));
    }

    public function testNestedSwitchForwardMatchingRequest_ReturnsEndpointRouteResponse()
    {
        $splitter = new Route\Splitter\PathSwitch([
            'A' => new Route\Splitter\PathSwitch([
                'A' => $this->responseRoute($responseAA),
                'B' => $this->responseRoute($responseAB)
            ]),
            'B' => new Route\Splitter\PathSwitch([
                'A' => $this->responseRoute($responseBA),
                'B' => $this->responseRoute($responseBB)
            ])
        ]);

        $this->assertSame(self::$prototype, $this->routeForwardCall($splitter, 'A/C/B'));
        $this->assertSame($responseAB, $this->routeForwardCall($splitter, '/A/B/C/D'));
        $this->assertSame($responseBA, $this->routeForwardCall($splitter, 'B/A/foo/bar'));
    }

    public function testSelect_ReturnsMatchingRouteWithPathWrapper()
    {
        $splitter = new Route\Splitter\PathSwitch([
            'A' => $routeA = new Doubles\MockedRoute(),
            'B' => $routeB = new Doubles\MockedRoute()
        ]);
        $this->assertEquals($this->patternGate('A', $routeA), $splitter->select('A'));
        $this->assertEquals($this->patternGate('B', $routeB), $splitter->select('B'));
    }

    public function testSelectNestedPathWithRoutePath_ReturnsSameRouteAsRepeatedSelectCall()
    {
        $splitter = $this->createStructure(Doubles\MockedRoute::response('endpoint'), ['foo', 'bar', 'baz']);
        $this->assertEquals($splitter->select('foo.bar.baz'), $splitter->select('foo')->select('bar')->select('baz'));
    }

    public function testSelectNestedRouteWithRoutePath_ReturnsRouteThatMatchesAllPathSegments()
    {
        $splitter = $this->splitter([
            'A' => $this->splitter([
                'A' => $this->responseRoute($responseAA),
                'B' => $this->responseRoute($responseAB)
            ]),
            'B' => $this->responseRoute($responseB)
        ]);
        $this->assertSame(self::$prototype, $this->routeForwardCall($splitter->select('A'), 'http://example.com/B/A/123'));
        $this->assertSame($responseB, $this->routeForwardCall($splitter->select('B'), 'http://example.com/B/A/123'));
        $this->assertSame($responseAA, $this->routeForwardCall($splitter->select('A.A'), 'http://example.com/A/A/123'));
        $this->assertSame($responseAB, $this->routeForwardCall($splitter->select('A.B'), 'A/B/foo/bar'));
    }

    public function testWhenNoRootRoute_UriMethodCall_ThrowsException()
    {
        $this->expectException(Exception\EndpointCallException::class);
        $this->splitter()->uri(new Doubles\FakeUri(), []);
    }

    public function testWithRootRoute_UriMethodCall_ReturnsRootUri()
    {
        $splitter = $this->splitter([], Doubles\MockedRoute::withUri('root'));
        $this->assertSame('root', (string) $splitter->uri(new Doubles\FakeUri(), []));
    }

    public function testWithRootRoute_UriOrForward_ReturnsResultsEquivalentToRootRouteCalls()
    {
        $splitter  = $this->splitter([], $this->responseRoute($response));
        $structure = $this->createStructure($splitter, ['foo', 'bar']);
        $wrapped   = $this->patternGate('foo', $this->patternGate('bar', $splitter));
        $implicit  = $structure->select('foo.bar');
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/foo/bar'));

        $this->assertEquals($wrapped, $implicit);
        $this->assertSame($response, $implicit->forward($request, self::$prototype));
        $this->assertEquals('/foo/bar', (string) $implicit->uri(new Doubles\FakeUri(), []));
    }

    /**
     * @dataProvider segmentCombinations
     *
     * @param array  $segments
     * @param string $prototype
     * @param string $expected
     */
    public function testEndpointUri_ReturnsUriThatCanReachEndpoint(array $segments, string $prototype, string $expected)
    {
        $prototype = Doubles\FakeUri::fromString($prototype);
        $path      = implode(Route::PATH_SEPARATOR, $segments);
        $splitter  = $this->createStructure($this->responseRoute($endpointResponse), $segments);
        $this->assertSame($expected, (string) $splitter->select($path)->uri($prototype, []));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString($expected));
        $this->assertSame($endpointResponse, $splitter->forward($request, self::$prototype));

        $request = new Doubles\FakeServerRequest('GET', $prototype);
        $this->assertSame(self::$prototype, $splitter->forward($request, self::$prototype));
    }

    public function segmentCombinations()
    {
        return [
            [['foo', 'bar'], 'http://example.com?query=string', 'http://example.com/foo/bar?query=string'],
            [['foo', 'bar', 'baz'], 'http://example.com?query=string', 'http://example.com/foo/bar/baz?query=string'],
            [['foo'], 'http:?query', 'http:/foo?query']
        ];
    }

    public function testRoutesMethod_AddsRouteTracedPathsToRoutingMap()
    {
        $splitter = new Route\Splitter\PathSwitch([
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute()
        ], new Doubles\MockedRoute());

        $map   = new Map();
        $trace = (new Map\Trace($map, Doubles\FakeUri::fromString('/path')))->nextHop('route');

        $splitter->routes($trace);
        $expected = [
            new Map\Path('route', '*', '/path'),
            new Map\Path('route.foo', '*', '/path/foo'),
            new Map\Path('route.bar', '*', '/path/bar')
        ];

        $this->assertEquals($expected, $map->paths());
    }

    public function testNameConflictWithDefaultRoute_ThrowsException()
    {
        $splitter = new Route\Splitter\PathSwitch([
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute()
        ], Doubles\MockedRoute::withTraceCallback(function (Map\Trace $trace) {
            $trace->nextHop('foo');
        }));

        $trace = new Map\Trace(new Map(), new Doubles\FakeUri());
        $this->expectException(Exception\UnreachableEndpointException::class);
        $splitter->routes($trace);
    }

    public function testRoutesMethodWithRootRoutePathExpansion_ThrowsException()
    {
        $splitter = new Route\Splitter\PathSwitch([
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute()
        ], Doubles\MockedRoute::withTraceCallback(function (Map\Trace $trace) {
            $trace->withPattern(new Route\Gate\Pattern\UriPart\PathSegment('baz'));
        }));

        $trace = new Map\Trace(new Map(), new Doubles\FakeUri());
        $this->expectException(Exception\UnreachableEndpointException::class);
        $splitter->routes($trace);
    }

    private function routeForwardCall(Route $route, string $requestUri = null): ResponseInterface
    {
        $uri     = $requestUri ? Doubles\FakeUri::fromString($requestUri) : new Doubles\FakeUri();
        $request = new Doubles\FakeServerRequest('GET', $uri);
        return $route->forward($request, self::$prototype);
    }

    private function createStructure(Route $route, array $segments)
    {
        while ($segment = array_pop($segments)) {
            $route = new Route\Splitter\PathSwitch([$segment => $route]);
        }
        return $route;
    }

    private function splitter(array $routes = [], Route $root = null)
    {
        $routes = $routes ?: ['dummy' => new Doubles\MockedRoute()];
        return $root ? new Route\Splitter\PathSwitch($routes, $root) : new Route\Splitter\PathSwitch($routes);
    }

    private function patternGate(string $name, Route $route): Route\Gate\PatternGate
    {
        return new Route\Gate\PatternGate(new Route\Gate\Pattern\UriPart\PathSegment($name), $route);
    }
}
