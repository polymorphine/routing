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
use Psr\Http\Message\ServerRequestInterface;


class CallbackSwitchTest extends TestCase
{
    private const TEST_ID = 'route.id';

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route\Splitter\CallbackSwitch::class, $this->splitter());
        $this->assertInstanceOf(Route::class, $this->splitter());
    }

    public function testRequestIsForwardedBasedOnCallbackResult()
    {
        $splitter = $this->splitter([
            'foo' => $this->responseRoute($fooResponse),
            'bar' => $this->responseRoute($barResponse)
        ]);
        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $splitter->forward($request, $prototype));
        $this->assertSame($fooResponse, $splitter->forward($request->withAttribute(self::TEST_ID, 'foo'), $prototype));
        $this->assertSame($barResponse, $splitter->forward($request->withAttribute(self::TEST_ID, 'bar'), $prototype));
    }

    public function testRoutesCanBeSelected()
    {
        $routes = [
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute()
        ];
        $splitter = $this->splitter($routes);

        $this->assertSame($routes['foo'], $splitter->select('foo'));
        $this->assertSame($routes['bar'], $splitter->select('bar'));
    }

    public function testSelectingSubRoutesCallsSelectOnSplitterRoutes()
    {
        $routes = [
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute()
        ];
        $splitter = $this->splitter($routes);

        $subRoute = $splitter->select('foo.bar.anything');
        $this->assertSame($subRoute, $routes['foo']->subRoute);
        $this->assertSame('bar.anything', $routes['foo']->path);

        $subRoute = $splitter->select('bar.path');
        $this->assertSame($subRoute, $routes['bar']->subRoute);
        $this->assertSame('path', $routes['bar']->path);
    }

    public function testUriMethod_ThrowsException()
    {
        $splitter = $this->splitter(['route' => new Doubles\MockedRoute()]);
        $this->expectException(Exception\EndpointCallException::class);
        $splitter->uri(new Doubles\FakeUri(), []);
    }

    public function testRoutesMethod_AddsRouteTracesToRoutingMap()
    {
        $splitter = $this->splitter([
            'foo' => new Doubles\MockedRoute(),
            'bar' => new Doubles\MockedRoute()
        ]);

        $uri   = '/foo/bar';
        $map   = new Map();
        $trace = (new Route\Trace($map, Doubles\FakeUri::fromString($uri)))->nextHop('path');

        $splitter->routes($trace);
        $expected = [
            'path.foo' => $uri,
            'path.bar' => $uri
        ];

        $this->assertSame($expected, $map->toArray());
    }

    private function splitter(array $routes = [])
    {
        $idCallback = function (ServerRequestInterface $request): string {
            return $request->getAttribute(self::TEST_ID, 'not.found');
        };
        return new Route\Splitter\CallbackSwitch($routes, $idCallback);
    }

    private function responseRoute(&$response, string $body = '')
    {
        $response = new Doubles\FakeResponse($body);
        return new Doubles\MockedRoute($response);
    }
}
