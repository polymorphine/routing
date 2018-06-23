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
use Polymorphine\Routing\Route\Pattern\StaticUriMask;
use Polymorphine\Routing\Route\Gate\PatternGate;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class PatternGateTest extends TestCase
{
    private static $prototype;

    public static function setUpBeforeClass()
    {
        self::$prototype = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(PatternGate::class, $default = $this->staticGate());
        $this->assertInstanceOf(PatternGate::class, $https = $this->staticGate('https:'));
        $this->assertInstanceOf(PatternGate::class, $http = $this->staticGate('http:'));

        $this->assertEquals($default, $https);
        $this->assertNotEquals($default, $http);

        $gateway = PatternGate::withPatternString('/test/{#testId}', MockedRoute::response('default'));
        $this->assertInstanceOf(PatternGate::class, $gateway);

        $gateway = PatternGate::withPatternString('//domain.com/test/foo', MockedRoute::response('default'));
        $this->assertInstanceOf(PatternGate::class, $gateway);
    }

    public function testNotMatchingPattern_ReturnsPrototypeInstance()
    {
        $request = $this->request('http:/some/path');
        $this->assertSame(self::$prototype, $this->staticGate('https:/some/path')->forward($request, self::$prototype));
        $request = $this->request('http://example.com/foo/bazzzz');
        $this->assertSame(self::$prototype, $this->staticGate('example.com/foo/ba')->forward($request, self::$prototype));
    }

    public function testMatchingPattern_ReturnsForwardedRouteResponse()
    {
        $request = $this->request('http://example.com/some/path');
        $this->assertNotSame(self::$prototype, $this->staticGate('//example.com')->forward($request, self::$prototype));
        $request = $this->request('http://www.example.com?foo=bar');
        $this->assertNotSame(self::$prototype, $this->staticGate('http:?foo=bar')->forward($request, self::$prototype));
    }

    public function testUri_ReturnsUriWithPatternDefinedSegments()
    {
        $subRoute = MockedRoute::withUri('/foo/bar');

        $uri = $this->staticGate('https:?some=query', $subRoute)->uri(new FakeUri(), []);
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame('some=query', $uri->getQuery());

        $uri = $this->staticGate('//example.com', $subRoute)->uri(new FakeUri(), []);
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
    }

    public function testSelectMethod_ReturnsRouteProducingUriWithDefinedSegments()
    {
        $subRoute = MockedRoute::withUri('/foo/bar');

        $uri = $this->staticGate('https://example.com', $subRoute)->select('some.path')->uri(new FakeUri(), []);
        $this->assertSame('https://example.com/foo/bar', (string) $uri);
        $uri = $this->staticGate('http:', $subRoute)->select('some.path')->uri(new FakeUri(), []);
        $this->assertSame('http:/foo/bar', (string) $uri);
    }

    public function testComposedRoutesUriCall_ReturnsUriWithSegmentsDefinedInAllRoutes()
    {
        $subRoute = $this->staticGate('//example.com', MockedRoute::withUri('/foo/bar'));
        $uri      = $this->staticGate('https:', $subRoute)->select('some.path')->uri(new FakeUri(), []);
        $this->assertSame('https://example.com/foo/bar', (string) $uri);
    }

    public function testComposedRelativePathsAreJoinedInCorrectOrder()
    {
        $proto = FakeUri::fromString('/foo');
        $route = PatternGate::withPatternString('{$bar}', PatternGate::withPatternString('{#baz}', MockedRoute::response('endpoint')));
        $this->assertSame('/foo/bar/123', $uri = (string) $route->uri($proto, ['bar' => 'bar', 'baz' => 123]));
        $this->assertSame('endpoint', (string) $route->forward($this->request($uri), new FakeResponse('proto'))->getBody());

        $proto = FakeUri::fromString('/foo');
        $route = PatternGate::withPatternString('bar*', PatternGate::withPatternString('baz', MockedRoute::response('endpoint')));
        $this->assertSame('/foo/bar/baz', $uri = (string) $route->uri($proto, []));
        $request = $this->request($uri)->withAttribute(Route::PATH_ATTRIBUTE, 'bar/baz');
        $this->assertSame('endpoint', (string) $route->forward($request, new FakeResponse('proto'))->getBody());
    }

    public function testComposedRelativePathsMatchesChangeContextForNextMatch()
    {
        $request = new FakeServerRequest('GET', FakeUri::fromString('/foo/bar/baz'));
        $proto   = new FakeResponse('prototype');

        $route = PatternGate::withPatternString('bar', PatternGate::withPatternString('bar', MockedRoute::response('endpoint')));
        $this->assertSame($proto, $route->forward($request, $proto));
    }

    private function staticGate(string $uriPattern = 'https:', $subRoute = null)
    {
        return new PatternGate(
            new StaticUriMask($uriPattern),
            $subRoute ?: MockedRoute::response('default')
        );
    }

    private function request($uri = 'http://example.com/foo/bar?query=string')
    {
        $request      = new FakeServerRequest();
        $request->uri = FakeUri::fromString($uri);

        return $request;
    }
}
