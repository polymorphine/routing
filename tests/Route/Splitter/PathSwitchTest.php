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
use Polymorphine\Routing\Route\Splitter\PathSwitch;
use Polymorphine\Routing\Route\Gate;
use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use Polymorphine\Routing\Tests\Doubles;
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
        $splitter = new PathSwitch([
            'A' => new PathSwitch([
                'A' => $this->responseRoute($responseAA),
                'B' => $this->responseRoute($responseAB)
            ]),
            'B' => new PathSwitch([
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
        $splitter = new PathSwitch([
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
        $this->expectException(EndpointCallException::class);
        $this->splitter()->uri(new Doubles\FakeUri(), []);
    }

    public function testWithRootRoute_UriMethodCall_ReturnsRootUri()
    {
        $splitter = $this->splitter([], Doubles\MockedRoute::withUri('root'));
        $this->assertSame('root', (string) $splitter->uri(new Doubles\FakeUri(), []));
    }

    public function testRootRouteCanBeSelected()
    {
        $splitter = $this->splitter([], $root = new Doubles\MockedRoute());
        $root     = new PathSwitch([], $root);
        $this->assertEquals($root, $splitter->select(PathSwitch::ROOT_PATH));
    }

    public function testRootRouteLabelCanBeSetAtInstantiation()
    {
        $splitter = new PathSwitch(['dummy' => new Doubles\MockedRoute()], $root = new Doubles\MockedRoute(), 'rootLabel');
        $root     = new PathSwitch([], $root, 'rootLabel');
        $this->assertEquals($root, $splitter->select('rootLabel'));
    }

    public function testWithRootRoute_UriOrForward_ReturnsResultsEquivalentToRootRouteCalls()
    {
        $splitter  = $this->splitter([], $this->responseRoute($response));
        $structure = $this->createStructure($splitter, ['foo', 'bar']);
        $wrapped   = $this->patternGate('foo', $this->patternGate('bar', $splitter));
        $implicit  = $structure->select('foo.bar');
        $explicit  = $structure->select('foo.bar.' . PathSwitch::ROOT_PATH);
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/foo/bar'));

        $this->assertEquals($wrapped, $implicit);
        $this->assertNotEquals($implicit, $explicit);
        $this->assertSame($response, $implicit->forward($request, self::$prototype));
        $this->assertSame($response, $explicit->forward($request, self::$prototype));
        $this->assertEquals('/foo/bar', (string) $implicit->uri(new Doubles\FakeUri(), []));
        $this->assertEquals('/foo/bar', (string) $explicit->uri(new Doubles\FakeUri(), []));
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

    public function testRoutesMethod_AddsRouteTracesToRoutingMap()
    {
        $splitter = new PathSwitch([
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute()
        ], new Doubles\MockedRoute());

        $map   = new Map();
        $uri   = '/path';
        $trace = (new Map\Trace($map, Doubles\FakeUri::fromString($uri)))->nextHop('path');

        $splitter->routes($trace);
        $expected = [
            'path.ROOT' => ['uri' => $uri, 'method' => '*'],
            'path.foo'  => ['uri' => $uri . '/foo', 'method' => '*'],
            'path.bar'  => ['uri' => $uri . '/bar', 'method' => '*']
        ];

        $this->assertSame($expected, $map->toArray());
    }

    public function segmentCombinations()
    {
        return [
            [['foo', 'bar'], 'http://example.com?query=string', 'http://example.com/foo/bar?query=string'],
            [['foo', 'bar', 'baz'], 'http://example.com?query=string', 'http://example.com/foo/bar/baz?query=string'],
            [['foo'], 'http:?query', 'http:/foo?query']
        ];
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
            $route = new PathSwitch([$segment => $route]);
        }
        return $route;
    }

    private function splitter(array $routes = [], Route $root = null)
    {
        $routes = $routes ?: ['dummy' => new Doubles\MockedRoute()];
        return $root ? new PathSwitch($routes, $root) : new PathSwitch($routes);
    }

    private function patternGate(string $name, Route $route): Gate\PatternGate
    {
        return new Gate\PatternGate(new Gate\Pattern\UriPart\PathSegment($name), $route);
    }
}
