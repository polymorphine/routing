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
use Polymorphine\Routing\Builder\EndpointRouteBuilder;
use Polymorphine\Routing\Builder\Exception\BuilderLogicException;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Route\Endpoint\RedirectEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeContainer;
use Polymorphine\Routing\Tests\Doubles\FakeHandlerFactory;
use Polymorphine\Routing\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
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
        $this->assertInstanceOf(EndpointRouteBuilder::class, $root->route());
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

    public function testContainerMappingIsPassedToBuilderContext()
    {
        $container = new FakeContainer([
            'handler' => new FakeRequestHandler(new FakeResponse('handler response'))
        ]);

        $root    = $this->builder($container);
        $builder = $root->rootNode();
        $builder->endpointId(FakeHandlerFactory::class);
        $this->assertInstanceOf(CallbackEndpoint::class, $route = $builder->build());

        $request = (new FakeServerRequest())->withHeader('id', 'handler');
        $this->assertInstanceOf(ResponseInterface::class, $response = $route->forward($request, new FakeResponse()));
        $this->assertSame('handler response', (string) $response->getBody());
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
        return $container ? Builder::withContainer($container) : new Builder();
    }
}
