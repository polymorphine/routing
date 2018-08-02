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
use Polymorphine\Routing\Exception\BuilderCallException;


class ResponseScanSwitchBuilder extends SwitchBuilder
{
    /** @var Builder */
    protected $defaultRoute;

    public function route(string $name = null): RouteBuilder
    {
        return ($name) ? parent::route($name) : $this->builders[] = ($this->builderCallback)();
    }

    public function defaultRoute(): RouteBuilder
    {
        if (isset($this->defaultRoute)) {
            throw new BuilderCallException('Default route already set');
        }

        return $this->defaultRoute = ($this->builderCallback)();
    }

    protected function router(array $routes): Route
    {
        return $this->defaultRoute
            ? new Route\Splitter\ResponseScanSwitch($routes, $this->defaultRoute->build())
            : new Route\Splitter\ResponseScanSwitch($routes);
    }
}
