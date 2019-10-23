<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Splitter;

use Polymorphine\Routing\Route;


trait RouteSelectMethods
{
    private function splitPath(string $path): array
    {
        return explode(static::PATH_SEPARATOR, $path, 2) + [null, null];
    }

    private function getRoute(?string $id, ?string $path): Route
    {
        if (!$id) {
            throw Route\Exception\RouteNotFoundException::invalidGatewayPath();
        }

        if (!isset($this->routes[$id])) {
            throw Route\Exception\RouteNotFoundException::undefinedGateway($id);
        }

        return $path ? $this->nextSwitchRoute($this->routes[$id], $path) : $this->routes[$id];
    }

    private function nextSwitchRoute(Route $route, string $path): Route
    {
        return $route->select($path);
    }
}
