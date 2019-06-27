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
use Polymorphine\Routing\Builder\Node\ContextRouteNode;
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
        $this->assertInstanceOf(Builder::class, $this->root());
    }

    public function testRouterMethodWithoutSetup_ThrowsException()
    {
        $this->expectException(BuilderLogicException::class);
        $this->root()->router();
    }

    public function testWithContextSetupRouterMethod_ReturnsRouter()
    {
        $root = $this->root();
        $root->rootNode()->callback(function () {});
        $this->assertInstanceOf(Router::class, $root->router());
    }

    public function testSecondRootContext_ThrowsException()
    {
        $root = $this->root();
        $root->rootNode();
        $this->expectException(BuilderLogicException::class);
        $root->rootNode();
    }

    public function testEndpointMethod_ReturnsEndpointSetup()
    {
        $root = $this->root();
        $this->assertInstanceOf(DiscreteRouteBuilder::class, $root->route());
    }

    public function testBuilderMethod_ReturnsRouteBuilder()
    {
        $root = $this->root();
        $this->assertInstanceOf(ContextRouteNode::class, $root->detachedNode());
    }

    public function testWithoutContainerBuilderContextFactoryRoute_ThrowsException()
    {
        $builder = $this->root()->rootNode();
        $this->expectException(BuilderLogicException::class);
        $builder->factory(FakeHandlerFactory::class);
    }

    public function testContainerIsPassedToBuilderContext()
    {
        $root    = $this->root(new FakeContainer());
        $builder = $root->rootNode();
        $builder->factory(FakeHandlerFactory::class);
        $this->assertInstanceOf(HandlerFactoryEndpoint::class, $builder->build());
    }

    public function testRouterCallbackIsPassedToBuilderContext()
    {
        $root    = $this->root();
        $builder = $root->rootNode();
        $builder->redirect('routing.path');
        $this->assertInstanceOf(RedirectEndpoint::class, $builder->build());
    }

    private function root(ContainerInterface $container = null): Builder
    {
        return $container
            ? new Builder(new FakeUri(), new FakeResponse(), $container)
            : new Builder(new FakeUri(), new FakeResponse());
    }
}
