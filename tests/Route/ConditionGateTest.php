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
use Polymorphine\Routing\Route\ConditionGate;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;


class ConditionGateTest extends TestCase
{
    private static $prototype;

    public static function setUpBeforeClass()
    {
        self::$prototype = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testNotMatchingPath_ReturnsPrototypeInstance()
    {
        $route = $this->route(function () { return false; });
        $this->assertSame(self::$prototype, $route->forward($this->request(), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward($this->request('/bar/foo'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward($this->request('anything'), self::$prototype));
    }

    public function testMatchingPathForwardsRequest()
    {
        $route = $this->route();
        $this->assertNotSame(self::$prototype, $route->forward($this->request('/foo/bar'), self::$prototype));
        $this->assertSame('default', $route->forward($this->request('/foo/bar'), self::$prototype)->body);

        $route    = $this->route(function ($request) { return $request instanceof FakeServerRequest; });
        $response = $route->forward($this->request('anything'), self::$prototype);
        $this->assertNotSame(self::$prototype, $response);
        $this->assertSame('default', $response->body);
    }

    public function testGatewayCallIsPassedToWrappedRoute()
    {
        $route = $this->route();
        $this->assertSame('path.forwarded', $route->route('path.forwarded')->path);
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
        return new ConditionGate($closure, $route ?: new MockedRoute('default'));
    }

    private function request($path = '/')
    {
        $request      = new FakeServerRequest();
        $request->uri = FakeUri::fromString('//example.com' . $path);
        return $request;
    }
}
