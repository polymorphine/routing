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
use Polymorphine\Routing\Builder\EndpointSetup;
use Polymorphine\Routing\Builder\RouteBuilder;
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Route\Endpoint\HandlerEndpoint;
use Polymorphine\Routing\Route\Endpoint\HandlerFactoryEndpoint;
use Polymorphine\Routing\Route\Endpoint\RedirectEndpoint;
use Polymorphine\Routing\Route\Gate\LazyRoute;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeContainer;
use Polymorphine\Routing\Tests\Doubles\FakeHandlerFactory;
use Polymorphine\Routing\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Psr\Container\ContainerInterface;


class EndpointSetupTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(EndpointSetup::class, $this->builder());
    }

    public function testRouteBuildingMethodsWithoutGateWrappers_ReturnConcreteRoutes()
    {
        $this->assertInstanceOf(CallbackEndpoint::class, $this->builder()->callback(function () {}));

        $this->assertInstanceOf(LazyRoute::class, $this->builder()->lazy(function () {}));

        $route = new MockedRoute();
        $this->assertSame($route, $this->builder()->join($route));

        $this->assertInstanceOf(RedirectEndpoint::class, $this->builder(null, function () {})->redirect('some.route'));

        $handler = new FakeRequestHandler(new FakeResponse());
        $this->assertInstanceOf(HandlerEndpoint::class, $this->builder(new FakeContainer())->handler($handler));

        $this->assertInstanceOf(HandlerFactoryEndpoint::class, $this->builder(new FakeContainer())->factory(FakeHandlerFactory::class));
    }

    private function builder(?ContainerInterface $container = null, ?callable $router = null): EndpointSetup
    {
        return new EndpointSetup($container, $router);
    }
}
