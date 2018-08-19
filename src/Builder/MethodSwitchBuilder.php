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
use InvalidArgumentException;


class MethodSwitchBuilder implements Builder
{
    use CompositeBuilderMethods;

    private $methods = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'];

    public function __construct(?RouteBuilder $context = null, array $routes = [])
    {
        $this->context = $context ?? new RouteBuilder();
        $this->routes  = $routes;
    }

    public function get(): RouteBuilder
    {
        return $this->addBuilder('GET');
    }

    public function post(): RouteBuilder
    {
        return $this->addBuilder('POST');
    }

    public function patch(): RouteBuilder
    {
        return $this->addBuilder('PATCH');
    }

    public function put(): RouteBuilder
    {
        return $this->addBuilder('PUT');
    }

    public function delete(): RouteBuilder
    {
        return $this->addBuilder('DELETE');
    }

    public function route(string $name): RouteBuilder
    {
        $builder = $this->context->route();
        $names   = explode('|', $name);
        foreach ($names as $name) {
            $this->builders[$this->validMethod($name)] = $builder;
        }

        return $builder;
    }

    protected function router(array $routes): Route
    {
        return new Route\Splitter\MethodSwitch($routes);
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
