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
use Polymorphine\Routing\Builder\ResourceSwitchBuilder\ResourceFormsBuilder;
use Polymorphine\Routing\Route;


class RouteScanBuilder implements Builder
{
    use CompositeBuilderMethods;

    /** @var bool */
    private $hasDefaultRoute = false;
    private $resourcesForms;

    public function __construct(?BuilderContext $context = null, array $routes = [])
    {
        $this->context = $context ?? new BuilderContext();
        $this->routes  = $routes;
    }

    public function defaultRoute(): ContextRouteBuilder
    {
        if ($this->hasDefaultRoute) {
            throw new Exception\BuilderLogicException('Default route already set');
        }

        array_unshift($this->builders, $defaultRouteBuilder = $this->context->create());
        $this->hasDefaultRoute = true;
        return new ContextRouteBuilder($defaultRouteBuilder);
    }

    public function route(string $name = null): ContextRouteBuilder
    {
        return $this->addBuilder($name);
    }

    public function resource(string $name, array $routes = []): ResourceSwitchBuilder
    {
        if ($this->resourcesForms) {
            $formsBuilder = new ResourceFormsBuilder($name, $this->resourcesForms);
            return $this->route($name)->path($name)->resource($routes, $formsBuilder);
        }

        return $this->route($name)->path($name)->resource($routes);
    }

    public function withResourcesFormsPath(string $name): self
    {
        if ($this->resourcesForms) {
            throw new Exception\BuilderLogicException('Route path for resource forms already defined');
        }

        $this->resourcesForms = $this->route($name)->path($name)->pathSwitch();
        return $this;
    }

    protected function router(array $routes): Route
    {
        if (!$this->hasDefaultRoute) {
            return new Route\Splitter\RouteScan($routes);
        }

        $defaultRoute = array_shift($routes);
        return new Route\Splitter\RouteScan($routes, $defaultRoute);
    }
}
