<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder;

use Polymorphine\Routing\Route\Endpoint\HandlerFactoryEndpoint;
use Polymorphine\Routing\Route\Endpoint\RedirectEndpoint;
use Psr\Container\ContainerInterface;


class ContainerRouteBuilder extends RouteBuilder
{
    private $container;
    private $routerId;

    public function __construct(ContainerInterface $container, string $routerId)
    {
        $this->container = $container;
        $this->routerId  = $routerId;
    }

    public function redirect(string $path, int $code = 301): void
    {
        $uriCallback = function () use ($path) {
            return (string) $this->container->get($this->routerId)->uri($path);
        };

        $this->setRoute(new RedirectEndpoint($uriCallback, $code));
    }

    public function factory(string $className): void
    {
        $factoryCallback = function () use ($className) {
            return new $className();
        };

        $this->setRoute(new HandlerFactoryEndpoint($factoryCallback, $this->container));
    }
}
