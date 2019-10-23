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


/**
 * Builder Node creating and configuring PathSwitch splitter route.
 *
 * @see \Polymorphine\Routing\Route\Splitter\PathSwitch
 */
class PathSwitchNode implements Node
{
    use CompositeBuilderMethods;

    private $resourcesForms;

    /** @var Context */
    private $rootNode;

    public function __construct(Context $context, array $routes = [])
    {
        $this->context = $context;
        $this->routes  = $routes;
    }

    /**
     * Creates builder context for route accessible through $name
     * path segment in URI.
     *
     * @param string $name
     *
     * @return RouteNode
     */
    public function route(string $name): RouteNode
    {
        if (!$name) {
            throw new InvalidArgumentException('Name is required for path segment route switch');
        }

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
    public function resource(string $name, array $routes = []): ResourceSwitchNode
    {
        if ($this->resourcesForms) {
            $formsContext = new FormsContext($name, $this->resourcesForms);
            return $this->route($name)->path($name)->resource($routes, $formsContext);
        }

        return $this->route($name)->resource($routes);
    }

    /**
     * Creates separate path for resource forms configured from resource
     * builder context.
     *
     * @see PathSwitchNode::resource()
     *
     * @param string $name path segment to resource form endpoints
     *
     * @return PathSwitchNode
     */
    public function withResourcesFormsPath(string $name): self
    {
        if ($this->resourcesForms) {
            throw Exception\BuilderLogicException::resourceFormsAlreadySet();
        }

        $this->resourcesForms = $this->route($name)->pathSwitch();
        return $this;
    }

    /**
     * Creates builder context for route which URI path ends in
     * PathSwitch splitter with no continued path resolution.
     *
     * @see \Polymorphine\Routing\Route\Splitter\PathSwitch constructor
     * description for more information on root route.
     *
     * @return RouteNode
     */
    public function root(): RouteNode
    {
        if ($this->rootNode) {
            throw Exception\BuilderLogicException::rootPathRouteAlreadyDefined();
        }

        $this->rootNode = $this->context->create();
        return new RouteNode($this->rootNode);
    }

    protected function router(array $routes): Route
    {
        if ($this->rootNode) {
            return new Route\Splitter\PathSwitch($routes, $this->rootNode->build());
        }

        return new Route\Splitter\PathSwitch($routes);
    }
}
