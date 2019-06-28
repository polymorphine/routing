<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Node;

use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Builder\Exception;
use Polymorphine\Routing\Builder\Node\Resource\ResourceSwitchNode;
use Polymorphine\Routing\Builder\Node\Resource\FormsContext;
use Polymorphine\Routing\Route;
use InvalidArgumentException;


class PathSwitchNode implements Node
{
    use CompositeBuilderMethods;

    private $resourcesForms;
    private $rootLabel;

    public function __construct(Context $context, array $routes = [])
    {
        $this->context = $context;
        $this->routes  = $routes;
    }

    public function route(string $name): RouteNode
    {
        if (!$name) {
            throw new InvalidArgumentException('Name is required for path segment route switch');
        }

        return $this->addBuilder($name);
    }

    public function resource(string $name, array $routes = []): ResourceSwitchNode
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

    public function root(string $label = null): RouteNode
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
