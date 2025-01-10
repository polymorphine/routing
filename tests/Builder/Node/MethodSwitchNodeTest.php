<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Builder\Node;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Tests;
use Polymorphine\Routing\Tests\Doubles;
use InvalidArgumentException;


class MethodSwitchNodeTest extends TestCase
{
    use Tests\RoutingTestMethods;
    use Tests\Builder\ContextCreateMethod;

    public function test_Instantiation()
    {
        $this->assertInstanceOf(Builder\Node\MethodSwitchNode::class, $this->builder());
    }

    public function test_Build_ReturnsResponseScanSwitch()
    {
        $this->assertInstanceOf(Route\Splitter\MethodSwitch::class, $this->builder()->build());
    }

    public function test_Routes_CanBeAdded()
    {
        $switch = $this->builder();
        $switch->get()->callback($this->callbackResponse($get));
        $switch->post()->callback($this->callbackResponse($post));
        $switch->patch()->callback($this->callbackResponse($patch));
        $switch->put()->callback($this->callbackResponse($put));
        $switch->delete()->callback($this->callbackResponse($delete));
        $route = $switch->build();

        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($get, $route->forward($request->withMethod('GET'), $prototype));
        $this->assertSame($post, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($patch, $route->forward($request->withMethod('PATCH'), $prototype));
        $this->assertSame($put, $route->forward($request->withMethod('PUT'), $prototype));
        $this->assertSame($delete, $route->forward($request->withMethod('DELETE'), $prototype));
    }

    public function test_RouteForMultipleMethods_CanBeAdded()
    {
        $switch = $this->builder();
        $switch->get()->callback($this->callbackResponse($single));
        $switch->route('POST|PATCH')->callback($this->callbackResponse($multiple));
        $route = $switch->build();

        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($single, $route->forward($request->withMethod('GET'), $prototype));
        $this->assertSame($multiple, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($multiple, $route->forward($request->withMethod('PATCH'), $prototype));
    }

    public function test_ImplicitRoute_IsPassedToMethodSwitch()
    {
        $switch = $this->builder()->implicitPath('POST');
        $switch->post()->path('routeIMPLICIT')->callback(function () {});
        $route = $switch->build();
        $this->assertSame('/routeIMPLICIT', (string) $route->uri(new Doubles\FakeUri(), []));
    }

    public function test_RemovingImplicitRoute()
    {
        $switch = $this->builder()->explicitPath();
        $switch->post()->path('routePOST')->callback(function () {});
        $route = $switch->build();
        $this->assertSame('/routePOST', (string) $route->select('POST')->uri(new Doubles\FakeUri(), []));

        $this->expectException(Route\Exception\AmbiguousEndpointException::class);
        $route->uri(new Doubles\FakeUri(), []);
    }

    public function test_EmptyHttpMethodRouteName_ThrowsException()
    {
        $switch = $this->builder();
        $this->expectException(InvalidArgumentException::class);
        $switch->route('');
    }

    public function test_UnknownHttpMethodRouteName_ThrowsException()
    {
        $switch = $this->builder();
        $this->expectException(InvalidArgumentException::class);
        $switch->route('FOO');
    }

    public function test_AddingRouteWithAlreadyDefinedMethod_ThrowsException()
    {
        $switch = $this->builder();
        $switch->post()->callback(function () {});
        $this->expectException(Builder\Exception\BuilderLogicException::class);
        $switch->route('POST');
    }

    public function test_RepeatedMethodInMultipleMethodsParameter_ThrowsException()
    {
        $switch = $this->builder();
        $switch->post()->callback(function () {});
        $this->expectException(Builder\Exception\BuilderLogicException::class);
        $switch->route('GET|POST|PATCH');
    }

    private function builder(): Builder\Node\MethodSwitchNode
    {
        return new Builder\Node\MethodSwitchNode($this->context());
    }
}
