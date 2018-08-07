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
use Polymorphine\Routing\Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;


class RouteBuilder implements Builder
{
    use GateBuildMethods;

    /** @var Route $route */
    private $route;

    /** @var Builder $builder */
    private $builder;

    private $container;
    private $routerCallback;

    public function __construct(?ContainerInterface $container = null, ?callable $routerCallback = null)
    {
        $this->container      = $container;
        $this->routerCallback = $routerCallback;
    }

    public function route(): RouteBuilder
    {
        $clone = clone $this;

        $clone->builder = null;
        $clone->route   = null;
        $clone->gates   = [];

        return $clone;
    }

    public function build(): Route
    {
        if ($this->route) { return $this->route; }
        if (!$this->builder) {
            throw new Exception\BuilderCallException('Route type not selected');
        }

        return $this->route = $this->wrapGates($this->builder->build());
    }

    public function callback(callable $callback): void
    {
        $this->setRoute(new Route\Endpoint\CallbackEndpoint($callback));
    }

    public function handler(RequestHandlerInterface $handler): void
    {
        $this->setRoute(new Route\Endpoint\HandlerEndpoint($handler));
    }

    public function join(Route $route): void
    {
        $this->setRoute($route);
    }

    public function lazy(callable $routeCallback): void
    {
        $this->setRoute(new Route\Gate\LazyRoute($routeCallback));
    }

    public function redirect(string $path, int $code = 301): void
    {
        if (!$this->routerCallback) {
            throw new Exception\BuilderCallException('Required container aware builder to build redirect route');
        }

        $uriCallback = function () use ($path) {
            return (string) ($this->routerCallback)()->uri($path);
        };

        $this->setRoute(new Route\Endpoint\RedirectEndpoint($uriCallback, $code));
    }

    public function factory(string $className): void
    {
        if (!$this->container) {
            throw new Exception\BuilderCallException('Required container aware builder to build factory route');
        }

        $factoryCallback = function () use ($className) {
            return new $className();
        };

        $this->setRoute(new Route\Endpoint\HandlerFactoryEndpoint($factoryCallback, $this->container));
    }

    public function pathSwitch(array $routes = []): PathSegmentSwitchBuilder
    {
        return $this->switchBuilder(new PathSegmentSwitchBuilder($this, $routes));
    }

    public function responseScan(array $routes = []): ResponseScanSwitchBuilder
    {
        return $this->switchBuilder(new ResponseScanSwitchBuilder($this, $routes));
    }

    public function methodSwitch(array $routes = []): MethodSwitchBuilder
    {
        return $this->switchBuilder(new MethodSwitchBuilder($this, $routes));
    }

    public function resource(array $routes = []): ResourceSwitchBuilder
    {
        return $this->switchBuilder(new ResourceSwitchBuilder($this, $routes));
    }

    protected function setRoute(Route $route): void
    {
        $this->stateCheck();
        $this->route = $this->wrapGates($route);
    }

    protected function switchBuilder(Builder $builder)
    {
        $this->stateCheck();
        return $this->builder = $builder;
    }

    private function stateCheck(): void
    {
        if (!$this->route && !$this->builder) { return; }
        throw new Exception\BuilderCallException('Route already built');
    }
}
