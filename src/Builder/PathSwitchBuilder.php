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
use Polymorphine\Routing\Builder\Resource\ResourceSwitchBuilder;
use Polymorphine\Routing\Builder\Resource\FormsContext;
use Polymorphine\Routing\Route;
use InvalidArgumentException;


class PathSwitchBuilder implements Builder
{
    use CompositeBuilderMethods;

    private $resourcesForms;
    private $rootLabel;

    public function __construct(?BuilderContext $context = null, array $routes = [])
    {
        $this->context = $context ?? new BuilderContext();
        $this->routes  = $routes;
    }

    public function route(string $name): ContextRouteBuilder
    {
        if (!$name) {
            throw new InvalidArgumentException('Name is required for path segment route switch');
        }

        return $this->addBuilder($name);
    }

    public function resource(string $name, array $routes = []): ResourceSwitchBuilder
    {
        if ($this->resourcesForms) {
            $formsContext = new FormsContext($name, $this->resourcesForms);
            return $this->route($name)->path($name)->resource($routes, $formsContext);
        }

        return $this->route($name)->resource($routes);
    }

    public function withResourcesFormsPath(string $name): self
    {
        if ($this->resourcesForms) {
            throw new Exception\BuilderLogicException('Route path for resource forms already defined');
        }

        $this->resourcesForms = $this->route($name)->pathSwitch();
        return $this;
    }

    public function root(string $label = null): ContextRouteBuilder
    {
        if ($this->rootLabel) {
            throw new Exception\BuilderLogicException('Root path route already defined');
        }

        $this->rootLabel = $label ?: Route\Splitter\PathSwitch::ROOT_PATH;
        return $this->addBuilder($this->rootLabel);
    }

    protected function router(array $routes): Route
    {
        $rootRoute = $routes[$this->rootLabel] ?? null;
        if ($rootRoute) {
            unset($routes[$this->rootLabel]);
            return new Route\Splitter\PathSwitch($routes, $rootRoute, $this->rootLabel);
        }

        return new Route\Splitter\PathSwitch($routes);
    }
}
