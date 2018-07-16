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
use Exception;


class RouteBuilder implements Builder
{
    use GateBuildMethods;

    /** @var Route $route */
    private $route;

    /** @var Builder $builder */
    private $builder;

    public function build(): Route
    {
        if (!$this->route && !$this->builder) {
            throw new Exception('Route type not selected');
        }

        return $this->route ?: $this->wrapGates($this->builder->build());
    }

    public function callback(callable $callback): void
    {
        $this->setRoute(new CallbackEndpoint($callback));
    }

    public function join(Route $route): void
    {
        $this->setRoute($route);
    }

    public function pathSwitch(): RouteCollection
    {
        return $this->routeSwitch(new PathSegmentSwitchBuilder());
    }

    public function responseScan(): RouteCollection
    {
        return $this->routeSwitch(new ResponseScanSwitchBuilder());
    }

    public function methodSwitch(): RouteCollection
    {
        return $this->routeSwitch(new MethodSwitchBuilder());
    }

    protected function setRoute(Route $route): void
    {
        $this->stateCheck();
        $this->route = $this->wrapGates($route);
    }

    protected function routeSwitch(RouteCollection $builder): RouteCollection
    {
        $this->stateCheck();
        return $this->builder = $builder;
    }

    private function stateCheck(): void
    {
        if (!$this->route && !$this->builder) { return; }
        throw new Exception('Route already built');
    }
}
