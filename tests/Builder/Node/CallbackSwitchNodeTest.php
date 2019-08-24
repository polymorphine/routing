<?php

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
use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;


class CallbackSwitchNodeTest extends TestCase
{
    use Tests\RoutingTestMethods;
    use Tests\Builder\ContextCreateMethod;

    private const TEST_ID = 'route.id';

    public function testInstantiation()
    {
        $this->assertInstanceOf(Builder\Node::class, $builder = $this->builder());
        $this->assertInstanceOf(Builder\Node\CallbackSwitchNode::class, $builder);
    }

    public function testBuild_ReturnsCallbackSwitch()
    {
        $this->assertInstanceOf(Route\Splitter\CallbackSwitch::class, $this->builder()->build());
    }

    public function testRoutesCanBeAdded()
    {
        $switch = $this->builder([
            'baz' => new Tests\Doubles\MockedRoute($bazResponse = new Tests\Doubles\FakeResponse())
        ]);
        $switch->route('foo')->callback($this->callbackResponse($fooResponse));
        $switch->route('bar')->callback($this->callbackResponse($barResponse));
        $route = $switch->build();

        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $route->forward($request, $prototype));
        $this->assertSame($fooResponse, $route->forward($request->withAttribute(self::TEST_ID, 'foo'), $prototype));
        $this->assertSame($barResponse, $route->forward($request->withAttribute(self::TEST_ID, 'bar'), $prototype));
        $this->assertSame($bazResponse, $route->forward($request->withAttribute(self::TEST_ID, 'baz'), $prototype));
    }

    public function testEmptyIdName_ThrowsException()
    {
        $switch = $this->builder();
        $this->expectException(InvalidArgumentException::class);
        $switch->route('');
    }

    public function testAddingRouteWithAlreadyDefinedMethod_ThrowsException()
    {
        $switch = $this->builder();
        $switch->route('foo')->callback(function () {});
        $this->expectException(Builder\Exception\BuilderLogicException::class);
        $switch->route('foo');
    }

    private function builder(array $routes = [])
    {
        $idCallback = function (ServerRequestInterface $request): string {
            return $request->getAttribute(self::TEST_ID, 'not.found');
        };
        return new Builder\Node\CallbackSwitchNode($this->context(), $idCallback, $routes);
    }
}
