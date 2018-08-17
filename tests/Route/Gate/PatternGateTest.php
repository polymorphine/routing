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
use Polymorphine\Routing\Route\Gate\Pattern\UriPattern;
use Polymorphine\Routing\Route\Gate\PatternGate;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


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

        $gateway = PatternGate::withPatternString('/test/{#testId}', new MockedRoute());
        $this->assertInstanceOf(Route::class, $gateway);

        $gateway = PatternGate::withPatternString('//domain.com/test/foo', new MockedRoute());
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
        $subRoute = MockedRoute::withUri('/foo/bar');

        $uri = $this->patternGate('https:?some=query', $subRoute)->uri(new FakeUri(), []);
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame('some=query', $uri->getQuery());

        $uri = $this->patternGate('//example.com', $subRoute)->uri(new FakeUri(), []);
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
    }

    public function testSelectMethod_ReturnsRouteProducingUriWithDefinedSegments()
    {
        $route = $this->patternGate('https://example.com', MockedRoute::withUri('/foo/bar'));
        $this->assertSame('https://example.com/foo/bar', (string) $route->select('some.path')->uri(new FakeUri(), []));

        $route = $this->patternGate('http:', MockedRoute::withUri('/foo/bar'));
        $this->assertSame('http:/foo/bar', (string) $route->select('some.path')->uri(new FakeUri(), []));
    }

    public function testComposedRoutesUriCall_ReturnsUriWithSegmentsDefinedInAllRoutes()
    {
        $route = $this->patternGate('https:', $this->patternGate('//example.com', MockedRoute::withUri('/foo/bar')));
        $this->assertSame('https://example.com/foo/bar', (string) $route->select('some.path')->uri(new FakeUri(), []));
    }

    public function testComposedRelativePathsAreJoinedInCorrectOrder()
    {
        $prototype = FakeUri::fromString('/foo');
        $route     = PatternGate::withPatternString('{$bar}', PatternGate::withPatternString('{#baz}', MockedRoute::response('endpoint')));
        $this->assertSame('/foo/bar/123', $uri = (string) $route->uri($prototype, ['bar' => 'bar', 'baz' => 123]));
        $this->assertSame('endpoint', (string) $route->forward($this->request($uri), self::$prototype)->getBody());

        $prototype = FakeUri::fromString('/foo');
        $route     = PatternGate::withPatternString('bar*', PatternGate::withPatternString('baz', MockedRoute::response('endpoint')));
        $this->assertSame('/foo/bar/baz', $uri = (string) $route->uri($prototype, []));
        $request = $this->request($uri)->withAttribute(Route::PATH_ATTRIBUTE, 'bar/baz');
        $this->assertSame('endpoint', (string) $route->forward($request, self::$prototype)->getBody());
    }

    public function testComposedRelativePathsMatchesChangeContextForNextMatch()
    {
        $request = new FakeServerRequest('GET', FakeUri::fromString('/foo/bar/baz'));
        $route   = PatternGate::withPatternString('bar', PatternGate::withPatternString('bar', MockedRoute::response('endpoint')));
        $this->assertSame(self::$prototype, $route->forward($request, self::$prototype));
    }

    private function patternGate(string $uriPattern = 'https:', $subRoute = null)
    {
        return new PatternGate(UriPattern::fromUriString($uriPattern), $subRoute ?: MockedRoute::response('default'));
    }

    private function request($uri = 'http://example.com/foo/bar?query=string')
    {
        return new FakeServerRequest('GET', FakeUri::fromString($uri));
    }
}
