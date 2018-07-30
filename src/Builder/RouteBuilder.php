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
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Route\Endpoint\HandlerEndpoint;
use Polymorphine\Routing\Route\Gate\LazyRoute;
use Polymorphine\Routing\Exception\BuilderCallException;
use Psr\Http\Server\RequestHandlerInterface;


class RouteBuilder implements Builder
{
    use GateBuildMethods;

    /** @var Route $route */
    protected $route;

    /** @var Builder $builder */
    protected $builder;

    public function build(): Route
    {
        if ($this->route) { return $this->route; }
        if (!$this->builder) {
            throw new BuilderCallException('Route type not selected');
        }

        return $this->route = $this->wrapGates($this->builder->build());
    }

    public function callback(callable $callback): void
    {
        $this->setRoute(new CallbackEndpoint($callback));
    }

    public function handler(RequestHandlerInterface $handler): void
    {
        $this->setRoute(new HandlerEndpoint($handler));
    }

    public function join(Route $route): void
    {
        $this->setRoute($route);
    }

    public function lazy(callable $routeCallback)
    {
        $this->setRoute(new LazyRoute($routeCallback));
    }

    public function redirect(string $path): void
    {
        throw new BuilderCallException('Required container aware builder to build redirect route');
    }

    public function factory(string $className): void
    {
        throw new BuilderCallException('Required container aware builder to build factory route');
    }

    public function pathSwitch(): PathSegmentSwitchBuilder
    {
        return $this->switchBuilder(new PathSegmentSwitchBuilder($this->builderCallback()));
    }

    public function responseScan(): ResponseScanSwitchBuilder
    {
        return $this->switchBuilder(new ResponseScanSwitchBuilder($this->builderCallback()));
    }

    public function methodSwitch(): MethodSwitchBuilder
    {
        return $this->switchBuilder(new MethodSwitchBuilder($this->builderCallback()));
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

    protected function __clone()
    {
        $this->builder = null;
        $this->route   = null;
        $this->gates   = [];
    }

    private function stateCheck(): void
    {
        if (!$this->route && !$this->builder) { return; }
        throw new BuilderCallException('Route already built');
    }

    private function builderCallback(): callable
    {
        return function () {
            return clone $this;
        };
    }
}
