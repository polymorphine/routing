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


class ResponseScanSwitchBuilder implements Builder
{
    use CompositeBuilderMethods;

    /** @var Builder */
    private $defaultRoute;

    public function __construct(?BuilderContext $context = null, array $routes = [])
    {
        $this->context = $context ?? new BuilderContext();
        $this->routes  = $routes;
    }

    public function defaultRoute(): RouteBuilder
    {
        if (isset($this->defaultRoute)) {
            throw new Exception\BuilderLogicException('Default route already set');
        }

        return new RouteBuilder($this->defaultRoute = $this->context->create());
    }

    public function route(string $name = null): RouteBuilder
    {
        return $this->addBuilder($name);
    }

    public function resource(string $name, array $routes = []): ResourceSwitchBuilder
    {
        return $this->route($name)->path($name)->resource($routes);
    }

    protected function router(array $routes): Route
    {
        return $this->defaultRoute
            ? new Route\Splitter\ResponseScanSwitch($routes, $this->defaultRoute->build())
            : new Route\Splitter\ResponseScanSwitch($routes);
    }
}
