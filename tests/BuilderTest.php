<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Builder\Node\RouteNode;
use Polymorphine\Routing\Builder\DiscreteRouteBuilder;
use Polymorphine\Routing\Builder\Exception\BuilderLogicException;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Route\Endpoint\HandlerFactoryEndpoint;
use Polymorphine\Routing\Route\Endpoint\RedirectEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeContainer;
use Polymorphine\Routing\Tests\Doubles\FakeHandlerFactory;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Container\ContainerInterface;


class BuilderTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Builder::class, $this->builder());
    }

    public function testRouterMethodWithoutSetup_ThrowsException()
    {
        $this->expectException(BuilderLogicException::class);
        $this->builder()->router(new FakeUri(), new FakeResponse());
    }

    public function testWithContextSetupRouterMethod_ReturnsRouter()
    {
        $root = $this->builder();
        $root->rootNode()->callback(function () {});
        $this->assertInstanceOf(Router::class, $root->router(new FakeUri(), new FakeResponse()));
    }

    public function testSecondRootContext_ThrowsException()
    {
        $root = $this->builder();
        $root->rootNode();
        $this->expectException(BuilderLogicException::class);
        $root->rootNode();
    }

    public function testEndpointMethod_ReturnsEndpointSetup()
    {
        $root = $this->builder();
        $this->assertInstanceOf(DiscreteRouteBuilder::class, $root->route());
    }

    public function testBuilderMethod_ReturnsRouteBuilder()
    {
        $root = $this->builder();
        $this->assertInstanceOf(RouteNode::class, $root->detachedNode());
    }

    public function testWithoutContainerBuilderContextFactoryRoute_ThrowsException()
    {
        $builder = $this->builder()->rootNode();
        $this->expectException(BuilderLogicException::class);
        $builder->endpointId(FakeHandlerFactory::class);
    }

    public function testContainerIsPassedToBuilderContext()
    {
        $root    = $this->builder(new FakeContainer());
        $builder = $root->rootNode();
        $builder->endpointId(FakeHandlerFactory::class);
        $this->assertInstanceOf(HandlerFactoryEndpoint::class, $builder->build());
    }

    public function testRouterCallbackIsPassedToBuilderContext()
    {
        $root    = $this->builder();
        $builder = $root->rootNode();
        $builder->redirect('routing.path');
        $this->assertInstanceOf(RedirectEndpoint::class, $builder->build());
    }

    private function builder(ContainerInterface $container = null): Builder
    {
        return $container ? new Builder($container) : new Builder();
    }
}
