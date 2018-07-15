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

use Polymorphine\Routing\Route;
use InvalidArgumentException;


abstract class RoutingBuilder
{
    use GateBuildMethods;

    private $routes   = [];
    private $switches = [];

    public function build(): Route
    {
        foreach ($this->switches as $name => $switch) {
            $this->routes[$name] = $switch->build();
        }

        return $this->wrapGates($this->router($this->routes));
    }

    public function add(string $name, Route $route): void
    {
        $this->routes[$this->validName($name)] = $route;
    }

    public function endpoint(string $name): EndpointBuilder
    {
        return new EndpointBuilder($name, $this);
    }

    public function pathSwitch(string $name): RoutingBuilder
    {
        return $this->addSplitter($name, new PathSegmentSwitchBuilder());
    }

    public function responseScan(string $name): RoutingBuilder
    {
        return $this->addSplitter($name, new ResponseScanSwitchBuilder());
    }

    public function methodSwitch(string $name): RoutingBuilder
    {
        return $this->addSplitter($name, new MethodSwitchBuilder());
    }

    abstract protected function router(array $routes): Route;

    protected function validName(string $name): string
    {
        if (!isset($this->routes[$name])) { return $name; }

        $message = 'Route name `%s` already exists in this scope';
        throw new InvalidArgumentException(sprintf($message, $name));
    }

    private function addSplitter(string $name, RoutingBuilder $builder): RoutingBuilder
    {
        $this->routes[$this->validName($name)] = true;
        return $this->switches[$name]          = $builder;
    }
}
