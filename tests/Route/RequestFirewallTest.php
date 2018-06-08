<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\RequestFirewall;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;


class RequestFirewallTest extends TestCase
{
    private static $notFound;

    public static function setUpBeforeClass()
    {
        self::$notFound = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testNotMatchingPath_ReturnsNotFoundResponseInstance()
    {
        $route = $this->route(function () { return false; });
        $this->assertSame(self::$notFound, $route->forward($this->request(), self::$notFound));
        $this->assertSame(self::$notFound, $route->forward($this->request('/bar/foo'), self::$notFound));
        $this->assertSame(self::$notFound, $route->forward($this->request('anything'), self::$notFound));
    }

    public function testMatchingPathForwardsRequest()
    {
        $route = $this->route();
        $this->assertNotSame(self::$notFound, $route->forward($this->request('/foo/bar'), self::$notFound));
        $this->assertSame('default', $route->forward($this->request('/foo/bar'), self::$notFound)->body);

        $route    = $this->route(function ($request) { return $request instanceof FakeServerRequest; });
        $response = $route->forward($this->request('anything'), self::$notFound);
        $this->assertNotSame(self::$notFound, $response);
        $this->assertSame('default', $response->body);
    }

    public function testGatewayCallIsPassedToWrappedRoute()
    {
        $route = $this->route();
        $this->assertSame('path.forwarded', $route->gateway('path.forwarded')->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = $this->route(null, new MockedRoute($uri));
        $this->assertSame($uri, (string) $route->uri(new FakeUri(), []));
    }

    private function route($closure = null, $route = null)
    {
        $closure = $closure ?: function (ServerRequestInterface $request) {
            return strpos($request->getRequestTarget(), '/foo/bar') === 0;
        };
        return new RequestFirewall($closure, $route ?: new MockedRoute('default'));
    }

    private function request($path = '/')
    {
        $request      = new FakeServerRequest();
        $request->uri = FakeUri::fromString('//example.com' . $path);
        return $request;
    }
}
