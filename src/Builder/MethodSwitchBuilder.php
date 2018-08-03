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

use Polymorphine\Routing\Route;
use InvalidArgumentException;


class MethodSwitchBuilder extends SwitchBuilder
{
    private $methods = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'];

    public function route(string $name = null): RouteBuilder
    {
        if (!$name) {
            throw new InvalidArgumentException('Name is required for path segment route switch');
        }

        $builder = $this->context->route();
        $names   = explode('|', $name);
        foreach ($names as $name) {
            $this->addBuilder($builder, $this->validMethod($name));
        }

        return $builder;
    }

    protected function router(array $routes): Route
    {
        return new Route\Splitter\MethodSwitch($routes);
    }

    protected function validMethod(string $method): string
    {
        if (in_array($method, $this->methods, true)) { return $method; }

        $message = 'Unknown http method `%s` for method route switch';
        throw new InvalidArgumentException(sprintf($message, $method));
    }
}
