<?php declare(strict_types=1);

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
use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Route\Endpoint;
use Polymorphine\Routing\Route\Gate;
use Polymorphine\Routing\Tests\Doubles;


class EndpointRouteBuilderTest extends TestCase
{
    use ContextCreateMethod;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Builder\EndpointRouteBuilder::class, $this->builder());
    }

    public function testRouteBuildingMethodsWithoutGateWrappers_ReturnConcreteRoutes()
    {
        $callback = function () {};
        $this->assertInstanceOf(Endpoint\CallbackEndpoint::class, $this->builder()->callback($callback));
        $this->assertInstanceOf(Gate\LazyRoute::class, $this->builder()->lazy($callback));

        $route = new Doubles\MockedRoute();
        $this->assertSame($route, $this->builder()->joinRoute($route));

        $builder = $this->builder(null, function () {});
        $this->assertInstanceOf(Endpoint\RedirectEndpoint::class, $builder->redirect('some.route'));

        $handler = new Doubles\FakeRequestHandler(new Doubles\FakeResponse());
        $this->assertInstanceOf(Endpoint\HandlerEndpoint::class, $this->builder()->handler($handler));

        $builder = $this->builder(new Doubles\FakeContainer());
        $this->assertInstanceOf(Endpoint\CallbackEndpoint::class, $builder->endpointId(Doubles\FakeHandlerFactory::class));
    }

    private function builder($container = null, ?callable $router = null): Builder\EndpointRouteBuilder
    {
        return new Builder\EndpointRouteBuilder($this->context($container, $router));
    }
}
