<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Builder\Node\MethodSwitchNode;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use InvalidArgumentException;


class MethodSwitchBuilderTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(MethodSwitchNode::class, $this->builder());
    }

    public function testBuild_ReturnsResponseScanSwitch()
    {
        $this->assertInstanceOf(MethodSwitch::class, $this->builder()->build());
    }

    public function testRoutesCanBeAdded()
    {
        $switch = $this->builder();
        $switch->get()->callback($this->callbackResponse($get));
        $switch->post()->callback($this->callbackResponse($post));
        $switch->patch()->callback($this->callbackResponse($patch));
        $switch->put()->callback($this->callbackResponse($put));
        $switch->delete()->callback($this->callbackResponse($delete));
        $route = $switch->build();

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $this->assertSame($get, $route->forward($request->withMethod('GET'), $prototype));
        $this->assertSame($post, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($patch, $route->forward($request->withMethod('PATCH'), $prototype));
        $this->assertSame($put, $route->forward($request->withMethod('PUT'), $prototype));
        $this->assertSame($delete, $route->forward($request->withMethod('DELETE'), $prototype));
    }

    public function testRouteForMultipleMethodsCanBeAdded()
    {
        $switch = $this->builder();
        $switch->get()->callback($this->callbackResponse($single));
        $switch->route('POST|PATCH')->callback($this->callbackResponse($multiple));
        $route = $switch->build();

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $this->assertSame($single, $route->forward($request->withMethod('GET'), $prototype));
        $this->assertSame($multiple, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($multiple, $route->forward($request->withMethod('PATCH'), $prototype));
    }

    public function testImplicitRouteIsPassedToMethodSwitch()
    {
        $switch = $this->builder()->implicitPath('POST');
        $switch->post()->path('routeIMPLICIT')->callback(function () {});
        $route = $switch->build();
        $this->assertSame('/routeIMPLICIT', (string) $route->uri(new FakeUri(), []));
    }

    public function testRemovingImplicitRoute()
    {
        $switch = $this->builder()->explicitPath();
        $switch->post()->path('routePOST')->callback(function () {});
        $route = $switch->build();
        $this->assertSame('/routePOST', (string) $route->select('POST')->uri(new FakeUri(), []));

        $this->expectException(EndpointCallException::class);
        $route->uri(new FakeUri(), []);
    }

    public function testEmptyHttpMethodRouteName_ThrowsException()
    {
        $switch = $this->builder();
        $this->expectException(InvalidArgumentException::class);
        $switch->route('');
    }

    public function testUnknownHttpMethodRouteName_ThrowsException()
    {
        $switch = $this->builder();
        $this->expectException(InvalidArgumentException::class);
        $switch->route('FOO');
    }

    public function testAddingRouteWithAlreadyDefinedMethod_ThrowsException()
    {
        $switch = $this->builder();
        $switch->post()->callback(function () {});
        $this->expectException(InvalidArgumentException::class);
        $switch->route('POST');
    }

    public function testRepeatedMethodInMultipleMethodsParameter_ThrowsException()
    {
        $switch = $this->builder();
        $switch->post()->callback(function () {});
        $this->expectException(InvalidArgumentException::class);
        $switch->route('GET|POST|PATCH');
    }

    private function builder(): MethodSwitchNode
    {
        return new MethodSwitchNode();
    }
}
