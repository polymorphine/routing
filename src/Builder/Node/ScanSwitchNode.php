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
use Polymorphine\Routing\Route;


class ScanSwitchNode implements Node
{
    use CompositeBuilderMethods;

    /** @var bool */
    private $hasDefaultRoute = false;
    private $resourcesForms;

    public function __construct(Context $context, array $routes = [])
    {
        $this->context = $context;
        $this->routes  = $routes;
    }

    /**
     * Creates default route builder context.
     *
     * @see Route\Splitter\ScanSwitch constructor for more info
     * on default route.
     *
     * @return RouteNode
     */
    public function defaultRoute(): RouteNode
    {
        if ($this->hasDefaultRoute) {
            throw new Exception\BuilderLogicException('Default route already set');
        }

        array_unshift($this->builders, $defaultRouteBuilder = $this->context->create());
        $this->hasDefaultRoute = true;
        return new RouteNode($defaultRouteBuilder);
    }

    /**
     * Creates new route context.
     *
     * If $name is not supplied route URI cannot be built directly,
     * but may be accessed with URI built with another (alternative)
     * route.
     *
     * @param null|string $name
     *
     * @return RouteNode
     */
    public function route(string $name = null): RouteNode
    {
        return $this->addBuilder($name);
    }

    /**
     * Creates (REST) resource context builder accessed with path
     * segment given as $name parameter.
     *
     * @param string $name
     * @param array  $routes
     *
     * @return Resource\ResourceSwitchNode
     */
    public function resource(string $name, array $routes = []): Resource\ResourceSwitchNode
    {
        if ($this->resourcesForms) {
            $formsContext = new Resource\FormsContext($name, $this->resourcesForms);
            return $this->route($name)->path($name)->resource($routes, $formsContext);
        }

        return $this->route($name)->path($name)->resource($routes);
    }

    /**
     * Creates separate path for resource forms configured from resource
     * builder context.
     *
     * @see ScanSwitchNode::resource()
     *
     * @param string $name path segment to resource form endpoints
     *
     * @return ScanSwitchNode
     */
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
            return new Route\Splitter\ScanSwitch($routes);
        }

        $defaultRoute = array_shift($routes);
        return new Route\Splitter\ScanSwitch($routes, $defaultRoute);
    }
}
