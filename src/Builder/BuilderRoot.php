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

    public function useContainer(ContainerInterface $container, $routerId = null): void
    {
        $this->container = $container;

        if (!$routerId) { return; }
        $this->useRouterCallback(function () use ($routerId) { return $this->container->get($routerId); });
    }

    public function useRouterCallback(callable $routerCallback): void
    {
        $this->routerCallback = $routerCallback;
    }

    public function discreteBuilder(): DiscreteRouteBuilder
    {
        return new DiscreteRouteBuilder($this->createContext());
    }

    public function newContextBuilder(): ContextRouteBuilder
    {
        return new ContextRouteBuilder($this->createContext());
    }

    public function rootContextBuilder(): ContextRouteBuilder
    {
        if ($this->builder) {
            throw new Exception\BuilderLogicException('Root builder already defined');
        }
        $this->builder = $this->createContext();
        return new ContextRouteBuilder($this->builder);
    }

    public function createRouter(): Router
    {
        if (!$this->builder) {
            throw new Exception\BuilderLogicException('Root builder not defined');
        }
        return new Router($this->builder->build(), $this->baseUri, $this->nullResponse);
    }

    private function createContext(): BuilderContext
    {
        return new BuilderContext($this->container, $this->routerCallback);
    }
}
