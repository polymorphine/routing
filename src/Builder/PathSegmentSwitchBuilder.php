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


class PathSegmentSwitchBuilder extends SwitchBuilder
{
    private $rootRoute;

    public function route(string $name = null): RouteBuilder
    {
        if (!$name) {
            throw new InvalidArgumentException('Name is required for path segment route switch');
        }

        return parent::route($name);
    }

    public function resource(string $name, array $routes = []): ResourceSwitchBuilder
    {
        return parent::route($name)->resource($routes);
    }

    public function root(Route $root)
    {
        $this->rootRoute = $root;
    }

    protected function router(array $routes): Route
    {
        return new Route\Splitter\PathSegmentSwitch($routes, $this->rootRoute);
    }
}
