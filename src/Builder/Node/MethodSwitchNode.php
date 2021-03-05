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
use Polymorphine\Routing\Route;
use InvalidArgumentException;


/**
 * Builder Node creating and configuring MethodSwitch splitter route.
 * MethodSwitch route manages automatic OPTIONS resolving.
 *
 * @see \Polymorphine\Routing\Route\Splitter\MethodSwitch
 */
class MethodSwitchNode implements Node
{
    use CompositeBuilderMethods;

    private $implicitMethod = 'GET';
    private $methods        = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'];

    /**
     * @param Context $context
     * @param Route[] $routes
     */
    public function __construct(Context $context, array $routes = [])
    {
        $this->context = $context;
        $this->routes  = $routes;
    }

    /**
     * Removes implicit method (default: GET), which means that
     * selecting further route for URI building will require name
     * of the method in route path for each URI.
     *
     * @return MethodSwitchNode
     */
    public function explicitPath(): self
    {
        $this->implicitMethod = null;
        return $this;
    }

    /**
     * Changes implicit method (default: GET), which means that
     * selecting further route for given method will not require
     * name of the method to build URI. Useful when given method
     * can build URI for most of the routes.
     *
     * @param string $method
     *
     * @return MethodSwitchNode
     */
    public function implicitPath(string $method): self
    {
        $this->implicitMethod = $method;
        return $this;
    }

    /**
     * @see MethodSwitchNode::route('GET')
     *
     * @return RouteNode
     */
    public function get(): RouteNode
    {
        return $this->addBuilder('GET');
    }

    /**
     * @see MethodSwitchNode::route('POST')
     *
     * @return RouteNode
     */
    public function post(): RouteNode
    {
        return $this->addBuilder('POST');
    }

    /**
     * @see MethodSwitchNode::route('PATCH')
     *
     * @return RouteNode
     */
    public function patch(): RouteNode
    {
        return $this->addBuilder('PATCH');
    }

    /**
     * @see MethodSwitchNode::route('PUT')
     *
     * @return RouteNode
     */
    public function put(): RouteNode
    {
        return $this->addBuilder('PUT');
    }

    /**
     * @see MethodSwitchNode::route('DELETE')
     *
     * @return RouteNode
     */
    public function delete(): RouteNode
    {
        return $this->addBuilder('DELETE');
    }

    /**
     * Creates builder context for route accessible for requests
     * sent with $name method.
     *
     * @param string $name
     *
     * @return RouteNode
     */
    public function route(string $name): RouteNode
    {
        $context = $this->context->create();
        $names   = explode('|', $name);
        foreach ($names as $name) {
            $this->builders[$this->validMethod($name)] = $context;
        }

        return new RouteNode($context);
    }

    protected function router(array $routes): Route
    {
        return new Route\Splitter\MethodSwitch($routes, $this->implicitMethod);
    }

    protected function validMethod(string $method): string
    {
        if (!in_array($method, $this->methods, true)) {
            $message = 'Unknown http method `%s` for method route switch';
            throw new InvalidArgumentException(sprintf($message, $method));
        }

        return $this->validName($method);
    }
}
