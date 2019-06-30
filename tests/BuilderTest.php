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
use Polymorphine\Routing\Builder\Exception;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Route\Endpoint;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;


class BuilderTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Builder::class, $this->builder());
    }

    public function testRouterMethodWithoutSetup_ThrowsException()
    {
        $this->expectException(Exception\BuilderLogicException::class);
        $this->builder()->router(new Doubles\FakeUri(), new Doubles\FakeResponse());
    }

    public function testWithContextSetupRouterMethod_ReturnsRouter()
    {
        $root = $this->builder();
        $root->rootNode()->callback(function () {});
        $this->assertInstanceOf(Router::class, $root->router(new Doubles\FakeUri(), new Doubles\FakeResponse()));
    }

    public function testSecondRootContext_ThrowsException()
    {
        $root = $this->builder();
        $root->rootNode();
        $this->expectException(Exception\BuilderLogicException::class);
        $root->rootNode();
    }

    public function testEndpointMethod_ReturnsEndpointSetup()
    {
        $root = $this->builder();
        $this->assertInstanceOf(Builder\EndpointRouteBuilder::class, $root->route());
    }

    public function testBuilderMethod_ReturnsRouteBuilder()
    {
        $root = $this->builder();
        $this->assertInstanceOf(Builder\Node\RouteNode::class, $root->detachedNode());
    }

    public function testWithoutContainerBuilderContextFactoryRoute_ThrowsException()
    {
        $builder = $this->builder()->rootNode();
        $this->expectException(Exception\BuilderLogicException::class);
        $builder->endpointId(Doubles\FakeHandlerFactory::class);
    }

    public function testContainerMappingIsPassedToBuilderContext()
    {
        $container = new Doubles\FakeContainer([
            'handler' => new Doubles\FakeRequestHandler(new Doubles\FakeResponse('handler response'))
        ]);

        $root    = $this->builder($container);
        $builder = $root->rootNode();
        $builder->endpointId(Doubles\FakeHandlerFactory::class);
        $this->assertInstanceOf(Endpoint\CallbackEndpoint::class, $route = $builder->build());

        $request = (new Doubles\FakeServerRequest())->withHeader('id', 'handler');
        $this->assertInstanceOf(ResponseInterface::class, $response = $route->forward($request, new Doubles\FakeResponse()));
        $this->assertSame('handler response', (string) $response->getBody());
    }

    public function testRouterCallbackIsPassedToBuilderContext()
    {
        $root    = $this->builder();
        $builder = $root->rootNode();
        $builder->redirect('routing.path');
        $this->assertInstanceOf(Endpoint\RedirectEndpoint::class, $builder->build());
    }

    private function builder(ContainerInterface $container = null): Builder
    {
        return $container ? Builder::withContainer($container) : new Builder();
    }
}
