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


class MethodSwitchNode implements Node
{
    use CompositeBuilderMethods;

    private $implicitMethod = 'GET';
    private $methods        = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'];

    public function __construct(?Context $context = null, array $routes = [])
    {
        $this->context = $context ?? new Context();
        $this->routes  = $routes;
    }

    public function explicitPath(): self
    {
        $this->implicitMethod = null;
        return $this;
    }

    public function implicitPath(string $method): self
    {
        $this->implicitMethod = $method;
        return $this;
    }

    public function get(): RouteNode
    {
        return $this->addBuilder('GET');
    }

    public function post(): RouteNode
    {
        return $this->addBuilder('POST');
    }

    public function patch(): RouteNode
    {
        return $this->addBuilder('PATCH');
    }

    public function put(): RouteNode
    {
        return $this->addBuilder('PUT');
    }

    public function delete(): RouteNode
    {
        return $this->addBuilder('DELETE');
    }

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
