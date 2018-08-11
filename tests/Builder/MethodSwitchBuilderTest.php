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
use Polymorphine\Routing\Builder\SwitchBuilder;
use Polymorphine\Routing\Builder\MethodSwitchBuilder;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use InvalidArgumentException;


class MethodSwitchBuilderTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(SwitchBuilder::class, $this->builder());
    }

    public function testBuild_ReturnsResponseScanSwitch()
    {
        $this->assertInstanceOf(MethodSwitch::class, $this->builder()->build());
    }

    public function testRoutesCanBeAdded()
    {
        $switch = $this->builder();
        $switch->route('POST')->callback($this->responseCallback($post));
        $switch->route('GET')->callback($this->responseCallback($get));
        $route = $switch->build();

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $this->assertSame($post, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($get, $route->forward($request->withMethod('GET'), $prototype));
    }

    public function testRouteForMultipleMethodsCanBeAdded()
    {
        $switch = $this->builder();
        $switch->route('GET')->callback($this->responseCallback($single));
        $switch->route('POST|PATCH')->callback($this->responseCallback($multiple));
        $route = $switch->build();

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $this->assertSame($single, $route->forward($request->withMethod('GET'), $prototype));
        $this->assertSame($multiple, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($multiple, $route->forward($request->withMethod('PATCH'), $prototype));
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
        $switch->route('POST')->callback(function () {});
        $this->expectException(InvalidArgumentException::class);
        $switch->route('POST');
    }

    public function testRepeatedMethodInMultipleMethodsParameter_ThrowsException()
    {
        $switch = $this->builder();
        $switch->route('POST')->callback(function () {});
        $this->expectException(InvalidArgumentException::class);
        $switch->route('GET|POST|PATCH');
    }

    private function responseCallback(&$response)
    {
        $response = new FakeResponse();
        return function () use (&$response) {
            return $response;
        };
    }

    private function builder(): MethodSwitchBuilder
    {
        return new MethodSwitchBuilder();
    }
}
