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
use Polymorphine\Routing\Builder\BuilderRoot;
use Polymorphine\Routing\Builder\ContextRouteBuilder;
use Polymorphine\Routing\Builder\DiscreteRouteBuilder;
use Polymorphine\Routing\Builder\Exception\BuilderLogicException;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Route\Endpoint\HandlerFactoryEndpoint;
use Polymorphine\Routing\Route\Endpoint\RedirectEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeContainer;
use Polymorphine\Routing\Tests\Doubles\FakeHandlerFactory;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class BuilderRootTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(BuilderRoot::class, $this->root());
    }

    public function testRouterMethodWithoutSetup_ThrowsException()
    {
        $this->expectException(BuilderLogicException::class);
        $this->root()->createRouter();
    }

    public function testWithContextSetupRouterMethod_ReturnsRouter()
    {
        $root = $this->root();
        $root->rootContextBuilder()->callback(function () {});
        $this->assertInstanceOf(Router::class, $root->createRouter());
    }

    public function testSecondRootContext_ThrowsException()
    {
        $root = $this->root();
        $root->rootContextBuilder();
        $this->expectException(BuilderLogicException::class);
        $root->rootContextBuilder();
    }

    public function testEndpointMethod_ReturnsEndpointSetup()
    {
        $root = $this->root();
        $this->assertInstanceOf(DiscreteRouteBuilder::class, $root->discreteBuilder());
    }

    public function testBuilderMethod_ReturnsRouteBuilder()
    {
        $root = $this->root();
        $this->assertInstanceOf(ContextRouteBuilder::class, $root->newContextBuilder());
    }

    public function testWithoutContainerBuilderContextFactoryRoute_ThrowsException()
    {
        $builder = $this->root()->rootContextBuilder();
        $this->expectException(BuilderLogicException::class);
        $builder->factory(FakeHandlerFactory::class);
    }

    public function testContainerIsPassedToBuilderContext()
    {
        $root = $this->root();
        $root->useContainer(new FakeContainer());
        $builder = $root->rootContextBuilder();
        $builder->factory(FakeHandlerFactory::class);
        $this->assertInstanceOf(HandlerFactoryEndpoint::class, $builder->build());
    }

    public function testWithoutRouterCallbackBuilderContextRedirectRoute_ThrowsException()
    {
        $builder = $this->root()->rootContextBuilder();
        $this->expectException(BuilderLogicException::class);
        $builder->redirect('routing.path');
    }

    public function testRouterCallbackIsPassedToBuilderContext()
    {
        $root = $this->root();
        $root->useContainer(new FakeContainer(), 'container.routerId');
        $builder = $root->rootContextBuilder();
        $builder->redirect('routing.path');
        $this->assertInstanceOf(RedirectEndpoint::class, $builder->build());
    }

    private function root(): BuilderRoot
    {
        return new BuilderRoot(new FakeUri(), new FakeResponse());
    }
}
