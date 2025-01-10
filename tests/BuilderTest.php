<?php declare(strict_types=1);

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
use Polymorphine\Routing\Router;


class BuilderTest extends TestCase
{
    public function test_Instantiation()
    {
        $this->assertInstanceOf(Builder::class, $this->builder());
        $this->assertInstanceOf(Builder::class, $this->builder(new Doubles\FakeContainer()));
    }

    public function test_MissingRouterCallback_IsAddedToMappedRoutes()
    {
        $mappedRoutes = new Doubles\MockedMappedRoutes(null, null, null);
        $this->assertFalse($mappedRoutes->hasRouterCallback());
        new Builder($mappedRoutes);
        $this->assertTrue($mappedRoutes->modified);
        $this->assertTrue($mappedRoutes->hasRouterCallback());
    }

    public function test_RouterCallback_IsNotOverwrittenInMappedRoutes()
    {
        $mappedRoutes = new Doubles\MockedMappedRoutes(function () {}, null, null);
        $this->assertTrue($mappedRoutes->hasRouterCallback());
        new Builder($mappedRoutes);
        $this->assertFalse($mappedRoutes->modified);
    }

    public function test_RouterMethod_WithoutSetup_ThrowsException()
    {
        $this->expectException(Builder\Exception\BuilderLogicException::class);
        $this->builder()->router(new Doubles\FakeUri(), new Doubles\FakeResponse());
    }

    public function test_WithRootNodeDefined_RouterCanBeCreated()
    {
        $root = $this->builder();
        $root->rootNode()->callback(function () {});

        $router = $root->router(new Doubles\FakeUri(), new Doubles\FakeResponse());
        $this->assertInstanceOf(Router::class, $router);

        $router = $root->routerWithFactories(new Doubles\FakeUriFactory(), new Doubles\FakeResponseFactory());
        $this->assertInstanceOf(Router::class, $router);
    }

    public function test_SecondRootNode_ThrowsException()
    {
        $root = $this->builder();
        $root->rootNode();
        $this->expectException(Builder\Exception\BuilderLogicException::class);
        $root->rootNode();
    }

    public function test_EndpointMethod_ReturnsEndpointRouteBuilder()
    {
        $root = $this->builder();
        $this->assertInstanceOf(Builder\EndpointRouteBuilder::class, $root->route());
    }

    public function test_BuilderMethod_ReturnsBuilderRouteNode()
    {
        $root = $this->builder();
        $this->assertInstanceOf(Builder\Node\RouteNode::class, $root->detachedNode());
    }

    private function builder($container = null): Builder
    {
        return $container ? Builder::withContainer($container) : new Builder();
    }
}
