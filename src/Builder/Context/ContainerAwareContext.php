<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Context;

use Polymorphine\Routing\Builder\Context;
use Psr\Container\ContainerInterface;
use Polymorphine\Routing\Route;


class ContainerAwareContext extends Context
{
    private $container;

    public function __construct(callable $routerCallback, ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct($routerCallback);
    }

    public function mapEndpoint(string $factoryName): void
    {
        $factoryCallback = function () use ($factoryName) { return new $factoryName(); };
        $this->setRoute(new Route\Endpoint\HandlerFactoryEndpoint($factoryCallback, $this->container));
    }

    public function mapGate(string $middlewareContainerId): void
    {
        $this->addGate(function (Route $route) use ($middlewareContainerId) {
            return new Route\Gate\LazyRoute(function () use ($middlewareContainerId, $route) {
                $middleware = $this->container->get($middlewareContainerId);
                return new Route\Gate\MiddlewareGateway($middleware, $route);
            });
        });
    }
}
