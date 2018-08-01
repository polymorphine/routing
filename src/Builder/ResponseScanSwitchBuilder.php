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


class ResponseScanSwitchBuilder extends SwitchBuilder
{
    public function route(string $name = null): RouteBuilder
    {
        return ($name) ? parent::route($name) : $this->builders[] = ($this->builderCallback)();
    }

    protected function router(array $routes): Route
    {
        return new Route\Splitter\ResponseScanSwitch($routes);
    }
}
