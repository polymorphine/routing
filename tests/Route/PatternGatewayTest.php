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
use Polymorphine\Routing\Route\Pattern\StaticUriMask;
use Polymorphine\Routing\Route\PatternGateway;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class PatternGatewayTest extends TestCase
{
    private static $notFound;

    public static function setUpBeforeClass()
    {
        self::$notFound = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(PatternGateway::class, $default = $this->staticGate());
        $this->assertInstanceOf(PatternGateway::class, $https = $this->staticGate('https:'));
        $this->assertInstanceOf(PatternGateway::class, $http = $this->staticGate('http:'));

        $this->assertEquals($default, $https);
        $this->assertNotEquals($default, $http);

        $gateway = PatternGateway::withPatternString('/test/{#testId}', new MockedRoute('default'));
        $this->assertInstanceOf(PatternGateway::class, $gateway);

        $gateway = PatternGateway::withPatternString('//domain.com/test/foo', new MockedRoute('default'));
        $this->assertInstanceOf(PatternGateway::class, $gateway);
    }

    public function testNotMatchingPattern_ReturnsNotFoundResponseInstance()
    {
        $request = $this->request('http:/some/path');
        $this->assertSame(self::$notFound, $this->staticGate('https:/some/path')->forward($request, self::$notFound));
        $request = $this->request('http://example.com/foo/bazzzz');
        $this->assertSame(self::$notFound, $this->staticGate('example.com/foo/ba')->forward($request, self::$notFound));
    }

    public function testMatchingPattern_ReturnsForwardedRouteResponse()
    {
        $request = $this->request('http://example.com/some/path');
        $this->assertNotSame(self::$notFound, $this->staticGate('//example.com')->forward($request, self::$notFound));
        $request = $this->request('http://www.example.com?foo=bar');
        $this->assertNotSame(self::$notFound, $this->staticGate('http:?foo=bar')->forward($request, self::$notFound));
    }

    public function testUri_ReturnsUriWithPatternDefinedSegments()
    {
        $subRoute = new MockedRoute('/foo/bar');

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

    public function testGateway_ReturnsRouteProducingUriWithDefinedSegments()
    {
        $subRoute = new MockedRoute('/foo/bar');

        $uri = $this->staticGate('https://example.com', $subRoute)->gateway('some.path')->uri(new FakeUri(), []);
        $this->assertSame('https://example.com/foo/bar', (string) $uri);
        $uri = $this->staticGate('http:', $subRoute)->gateway('some.path')->uri(new FakeUri(), []);
        $this->assertSame('http:/foo/bar', (string) $uri);
    }

    public function testComposedGateway_ReturnsRouteProducingUriWithDefinedSegments()
    {
        $subRoute = $this->staticGate('//example.com', new MockedRoute('/foo/bar'));
        $uri      = $this->staticGate('https:', $subRoute)->gateway('some.path')->uri(new FakeUri(), []);
        $this->assertSame('https://example.com/foo/bar', (string) $uri);
    }

    private function staticGate(string $uriPattern = 'https:', $subRoute = null)
    {
        return new PatternGateway(
            new StaticUriMask($uriPattern),
            $subRoute ?: new MockedRoute('default')
        );
    }

    private function request($uri = 'http://example.com/foo/bar?query=string')
    {
        $request      = new FakeServerRequest();
        $request->uri = FakeUri::fromString($uri);

        return $request;
    }
}
