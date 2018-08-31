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

use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class BuilderRoot
{
    private $baseUri;
    private $nullResponse;

    /** @var ContainerInterface */
    private $container;
    private $routerCallback;

    /** @var Builder */
    private $builder;

    public function __construct(UriInterface $baseUri, ResponseInterface $nullResponse)
    {
        $this->baseUri      = $baseUri;
        $this->nullResponse = $nullResponse;
    }

    public function container(ContainerInterface $container, $routerId = null): void
    {
        $this->container = $container;

        if (!$routerId) { return; }
        $this->routerCallback(function () use ($routerId) { return $this->container->get($routerId); });
    }

    public function routerCallback(callable $routerCallback): void
    {
        $this->routerCallback = $routerCallback;
    }

    public function endpoint(): DiscreteRouteBuilder
    {
        return new DiscreteRouteBuilder(new BuilderContext($this->container, $this->routerCallback));
    }

    public function builder(): ContextRouteBuilder
    {
        return new ContextRouteBuilder(new BuilderContext($this->container, $this->routerCallback));
    }

    public function context(): ContextRouteBuilder
    {
        if ($this->builder) {
            throw new Exception\BuilderLogicException('Root builder already defined');
        }
        $this->builder = new BuilderContext($this->container, $this->routerCallback);
        return new ContextRouteBuilder($this->builder);
    }

    public function router(): Router
    {
        if (!$this->builder) {
            throw new Exception\BuilderLogicException('Root builder not defined');
        }
        return new Router($this->builder->build(), $this->baseUri, $this->nullResponse);
    }
}
