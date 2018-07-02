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


class MethodSwitchBuilder extends RoutingBuilder
{
    private $methods = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'];

    protected function router(array $routes): Route
    {
        return new Route\Splitter\MethodSwitch($routes);
    }

    protected function validName(string $name): string
    {
        if (in_array($name, $this->methods, true)) {
            return parent::validName($name);
        }

        $message = 'Unknown http method `%s` for method splitter route';
        throw new InvalidArgumentException(sprintf($message, $name));
    }
}
