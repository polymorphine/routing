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
use Polymorphine\Routing\Exception\SwitchCallException;
use Polymorphine\Routing\Tests\Doubles\MockedPattern;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\UriInterface;


class PatternEndpointTest extends TestCase
{
    private static $prototype;

    public static function setUpBeforeClass()
    {
        self::$prototype = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());

        $route = Route\PatternEndpoint::post('/home/{#id}', $this->dummyCallback());
        $this->assertInstanceOf(Route::class, $route);

        $route = Route\PatternEndpoint::get('http://example.com/home/path', $this->dummyCallback());
        $this->assertInstanceOf(Route::class, $route);
    }

    public function testNotMatchingRequest_ReturnsPrototypeInstance()
    {
        $route = $this->route('/page/3', 'GET', $this->dummyCallback());
        $this->assertSame(self::$prototype, $route->forward($this->request('/page/3', 'POST'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward($this->request('/page/4', 'GET'), self::$prototype));
    }

    public function testMatchingRequest_ReturnsEndpointResponse()
    {
        $response = $this->route('/page/3', 'GET', $this->dummyCallback())
                         ->forward($this->request('/page/3', 'GET'), self::$prototype);
        $this->assertNotSame(self::$prototype, $response);

        $response = $this->route('/page/576/foo-bar-45', 'UPDATE', $this->dummyCallback())
                         ->forward($this->request('/page/576/foo-bar-45', 'UPDATE'), self::$prototype);
        $this->assertNotSame(self::$prototype, $response);
    }

    public function testRequestIsForwardedWithMatchedAttributes()
    {
        $response = $this->route('/page/3', 'GET')
                         ->forward($this->request('/page/3', 'GET'), self::$prototype);
        $this->assertSame(['pattern' => 'passed'], $response->fromRequest->attr);

        $response = $this->route('/page/576/foo-bar-45', 'UPDATE')
                         ->forward($this->request('/page/576/foo-bar-45', 'UPDATE'), self::$prototype);
        $this->assertSame(['pattern' => 'passed'], $response->fromRequest->attr);
    }

    public function testUri_ReturnsUri()
    {
        $this->assertInstanceOf(UriInterface::class, $this->route('/foo/bar')->uri(new FakeUri(), []));
    }

    public function testGateway_ThrowsException()
    {
        $route = $this->route('//example.com');
        $this->expectException(SwitchCallException::class);
        $route->route('route.path');
    }

    private function route($path = '/', $method = 'GET', $callback = null)
    {
        return new Route\PatternEndpoint(
            $method,
            new MockedPattern($path),
            $callback ?: $this->dummyCallback()
        );
    }

    private function dummyCallback()
    {
        return function ($request) {
            $response              = new FakeResponse();
            $response->fromRequest = $request;
            return $response;
        };
    }

    private function request($path, $method)
    {
        $request         = new FakeServerRequest();
        $request->method = $method;
        $request->uri    = FakeUri::fromString($path);
        return $request;
    }
}
