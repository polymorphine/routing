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
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;


class CallbackSwitchTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route\Splitter\CallbackSwitch::class, $this->splitter());
        $this->assertInstanceOf(Route::class, $this->splitter());
    }

    public function testRequestIsForwardedBasedOnCallbackResult()
    {
        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $routes = [
            'foo' => $this->responseRoute($fooResponse),
            'bar' => $this->responseRoute($barResponse)
        ];

        $splitter = $this->splitter($routes, 'selected');
        $this->assertSame($prototype, $splitter->forward($request, $prototype));

        $splitter = $this->splitter($routes, 'foo');
        $this->assertSame($fooResponse, $splitter->forward($request, $prototype));

        $splitter = $this->splitter($routes, 'bar');
        $this->assertSame($barResponse, $splitter->forward($request, $prototype));
    }

    public function testRoutesCanBeSelected()
    {
        $routes = [
            'foo' => $this->responseRoute($fooResponse),
            'bar' => $this->responseRoute($barResponse)
        ];

        $splitter = $this->splitter($routes);
        $this->assertSame($routes['foo'], $splitter->select('foo'));
        $this->assertSame($routes['foo'], $splitter->select('foo.bar.anything'));
        $this->assertSame($routes['bar'], $splitter->select('bar'));
        $this->assertSame($routes['bar'], $splitter->select('bar.something'));
    }

    public function testUriMethod_ThrowsException()
    {
        $splitter = $this->splitter(['route' => new Doubles\MockedRoute()]);
        $this->expectException(Exception\EndpointCallException::class);
        $splitter->uri(new Doubles\FakeUri(), []);
    }

    private function splitter(array $routes = [], string $idStub = 'route')
    {
        $idCallback = function (ServerRequestInterface $request) use ($idStub): string {
            return $idStub;
        };
        return new Route\Splitter\CallbackSwitch($routes, $idCallback);
    }

    private function responseRoute(&$response, string $body = '')
    {
        $response = new Doubles\FakeResponse($body);
        return new Doubles\MockedRoute($response);
    }
}
