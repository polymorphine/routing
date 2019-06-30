<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use Polymorphine\Routing\Tests\Doubles;


class PatternGateTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $default = $this->patternGate());
        $this->assertInstanceOf(Route::class, $https = $this->patternGate('https:'));
        $this->assertInstanceOf(Route::class, $http = $this->patternGate('http:'));

        $this->assertEquals($default, $https);
        $this->assertNotEquals($default, $http);

        $gateway = Gate\PatternGate::withPatternString('/test/{#testId}', new Doubles\MockedRoute());
        $this->assertInstanceOf(Route::class, $gateway);

        $gateway = Gate\PatternGate::withPatternString('//domain.com/test/foo', new Doubles\MockedRoute());
        $this->assertInstanceOf(Route::class, $gateway);
    }

    public function testNotMatchingPattern_ReturnsPrototypeInstance()
    {
        $request = $this->request('http:/some/path');
        $this->assertSame(self::$prototype, $this->patternGate('https:/some/path')->forward($request, self::$prototype));
        $request = $this->request('http://example.com/foo/bazzzz');
        $this->assertSame(self::$prototype, $this->patternGate('example.com/foo/ba')->forward($request, self::$prototype));
    }

    public function testMatchingPattern_ReturnsForwardedRouteResponse()
    {
        $request = $this->request('http://example.com/some/path');
        $this->assertNotSame(self::$prototype, $this->patternGate('//example.com')->forward($request, self::$prototype));
        $request = $this->request('http://www.example.com?foo=bar');
        $this->assertNotSame(self::$prototype, $this->patternGate('http:?foo=bar')->forward($request, self::$prototype));
    }

    public function testUri_ReturnsUriWithPatternDefinedSegments()
    {
        $subRoute = Doubles\MockedRoute::withUri('/foo/bar');

        $uri = $this->patternGate('https:?some=query', $subRoute)->uri(new Doubles\FakeUri(), []);
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame('some=query', $uri->getQuery());

        $uri = $this->patternGate('//example.com', $subRoute)->uri(new Doubles\FakeUri(), []);
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
    }

    public function testSelectMethod_ReturnsRouteProducingUriWithDefinedSegments()
    {
        $route = $this->patternGate('https://example.com', Doubles\MockedRoute::withUri('/foo/bar'));
        $this->assertSame('https://example.com/foo/bar', (string) $route->select('some.path')->uri(new Doubles\FakeUri(), []));

        $route = $this->patternGate('http:', Doubles\MockedRoute::withUri('/foo/bar'));
        $this->assertSame('http:/foo/bar', (string) $route->select('some.path')->uri(new Doubles\FakeUri(), []));
    }

    public function testComposedRoutesUriCall_ReturnsUriWithSegmentsDefinedInAllRoutes()
    {
        $route = $this->patternGate('https:', $this->patternGate('//example.com', Doubles\MockedRoute::withUri('/foo/bar')));
        $this->assertSame('https://example.com/foo/bar', (string) $route->select('some.path')->uri(new Doubles\FakeUri(), []));
    }

    public function testComposedRelativePathsAreJoinedInCorrectOrder()
    {
        $prototype = Doubles\FakeUri::fromString('/foo');
        $route     = Gate\PatternGate::withPatternString('{$bar}', Gate\PatternGate::withPatternString('{#baz}', Doubles\MockedRoute::response('endpoint')));
        $this->assertSame('/foo/bar/123', $uri = (string) $route->uri($prototype, ['bar' => 'bar', 'baz' => 123]));
        $this->assertSame('endpoint', (string) $route->forward($this->request($uri), self::$prototype)->getBody());

        $prototype = Doubles\FakeUri::fromString('/foo');
        $route     = Gate\PatternGate::withPatternString('bar*', Gate\PatternGate::withPatternString('baz', Doubles\MockedRoute::response('endpoint')));
        $this->assertSame('/foo/bar/baz', $uri = (string) $route->uri($prototype, []));
        $request = $this->request($uri)->withAttribute(Route::PATH_ATTRIBUTE, 'bar/baz');
        $this->assertSame('endpoint', (string) $route->forward($request, self::$prototype)->getBody());
    }

    public function testComposedRelativePathsMatchesChangeContextForNextMatch()
    {
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/foo/bar/baz'));
        $route   = Gate\PatternGate::withPatternString('bar', Gate\PatternGate::withPatternString('bar', Doubles\MockedRoute::response('endpoint')));
        $this->assertSame(self::$prototype, $route->forward($request, self::$prototype));
    }

    public function testQueryPatternsAreJoinedTogetherOnCompositeRoute()
    {
        $prototype = Doubles\FakeUri::fromString('http://example.com/path');
        $route     = Gate\PatternGate::withPatternString('?foo=fizz', Gate\PatternGate::withPatternString('?bar=buzz', Doubles\MockedRoute::response('endpoint')));
        $this->assertSame('http://example.com/path?foo=fizz&bar=buzz', (string) $route->uri($prototype, []));
    }

    public function testCompositeQueryPatternsWithoutSpecifiedValueAreMatched()
    {
        $request = $this->request('http://example.com/path?foo=fizz&bar=buzz');
        $route   = Gate\PatternGate::withPatternString('?foo', Gate\PatternGate::withPatternString('?bar', $this->responseRoute($response)));
        $this->assertSame($response, $route->forward($request, self::$prototype));
    }

    private function patternGate(string $uriPattern = 'https:', $subRoute = null)
    {
        return new Gate\PatternGate(Gate\Pattern\UriPattern::fromUriString($uriPattern), $subRoute ?: Doubles\MockedRoute::response('default'));
    }

    private function request($uri = 'http://example.com/foo/bar?query=string')
    {
        return new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString($uri));
    }
}
