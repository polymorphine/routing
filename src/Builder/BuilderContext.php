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
use Polymorphine\Routing\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;


class BuilderContext implements Builder
{
    /** @var null|ContainerInterface */
    private $container;

    /** @var null|callable */
    private $routerCallback;

    /** @var null|Route */
    private $route;

    /** @var null|Builder */
    private $builder;

    /** @var callable[] */
    private $gates = [];

    /**
     * @param null|ContainerInterface $container
     * @param null|callable           $routerCallback function(): Router
     */
    public function __construct(?ContainerInterface $container = null, ?callable $routerCallback = null)
    {
        $this->container      = $container;
        $this->routerCallback = $routerCallback;
    }

    public function build(): Route
    {
        if ($this->route) { return $this->route; }
        if (!$this->builder) {
            throw new Exception\BuilderLogicException('Route type not selected');
        }
        return $this->route = $this->wrapRoute($this->builder->build());
    }

    public function create(): BuilderContext
    {
        $newContext = clone $this;

        $newContext->builder = null;
        $newContext->route   = null;
        $newContext->gates   = [];

        return $newContext;
    }

    /**
     * @param callable $routeWrapper function(Route): Route
     */
    public function addGate(callable $routeWrapper): void
    {
        $this->gates[] = $routeWrapper;
    }

    /**
     * @param callable $callback function(ServerRequestInterface): ResponseInterface
     */
    public function setCallbackRoute(callable $callback): void
    {
        $this->setRoute(new Route\Endpoint\CallbackEndpoint($callback));
    }

    public function setHandlerRoute(RequestHandlerInterface $handler): void
    {
        $this->setRoute(new Route\Endpoint\HandlerEndpoint($handler));
    }

    /**
     * @param callable $routeCallback function(): Route
     */
    public function setLazyRoute(callable $routeCallback): void
    {
        $this->setRoute(new Route\Gate\LazyRoute($routeCallback));
    }

    public function setRedirectRoute(string $routingPath, int $code = 301): void
    {
        $this->setRoute(new Route\Endpoint\RedirectEndpoint($this->uriCallback($routingPath), $code));
    }

    public function setFactoryRoute(string $className): void
    {
        $factoryCallback = function () use ($className) { return new $className(); };
        $this->setRoute(new Route\Endpoint\HandlerFactoryEndpoint($factoryCallback, $this->container()));
    }

    public function addContainerMiddlewareGate(string $middlewareContainerId)
    {
        $this->addGate(function (Route $route) use ($middlewareContainerId) {
            $middleware = new ContainerMiddleware($this->container(), $middlewareContainerId);
            return new Route\Gate\MiddlewareGateway($middleware, $route);
        });
    }

    public function setRoute(Route $route): void
    {
        $this->stateCheck();
        $this->route = $this->wrapRoute($route);
    }

    public function setBuilder(Builder $builder): void
    {
        $this->stateCheck();
        $this->builder = $builder;
    }

    private function wrapRoute(Route $route): Route
    {
        while ($gate = array_pop($this->gates)) {
            $route = $gate($route);
        }

        return $route;
    }

    private function container(): ContainerInterface
    {
        if (!$this->container) {
            throw new Exception\BuilderLogicException('Required container aware builder to build this route');
        }
        return $this->container;
    }

    private function uriCallback($routingPath): callable
    {
        if (!$this->routerCallback) {
            throw new Exception\BuilderLogicException('Required container aware builder to build redirect route');
        }

        return function () use ($routingPath) {
            return (string) ($this->routerCallback)()->uri($routingPath);
        };
    }

    private function stateCheck(): void
    {
        if (!$this->route && !$this->builder) { return; }
        throw new Exception\BuilderLogicException('Route already built');
    }
}
