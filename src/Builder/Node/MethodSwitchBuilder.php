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

use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Builder\BuilderContext;
use Polymorphine\Routing\Route;
use InvalidArgumentException;


class MethodSwitchBuilder implements Builder
{
    use CompositeBuilderMethods;

    private $implicitMethod = 'GET';
    private $methods = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'];

    public function __construct(?BuilderContext $context = null, array $routes = [])
    {
        $this->context = $context ?? new BuilderContext();
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

    public function get(): ContextRouteBuilder
    {
        return $this->addBuilder('GET');
    }

    public function post(): ContextRouteBuilder
    {
        return $this->addBuilder('POST');
    }

    public function patch(): ContextRouteBuilder
    {
        return $this->addBuilder('PATCH');
    }

    public function put(): ContextRouteBuilder
    {
        return $this->addBuilder('PUT');
    }

    public function delete(): ContextRouteBuilder
    {
        return $this->addBuilder('DELETE');
    }

    public function route(string $name): ContextRouteBuilder
    {
        $context = $this->context->create();
        $names   = explode('|', $name);
        foreach ($names as $name) {
            $this->builders[$this->validMethod($name)] = $context;
        }

        return new ContextRouteBuilder($context);
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
