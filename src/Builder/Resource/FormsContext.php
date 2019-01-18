<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Resource;

use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Route;


class FormsContext
{
    private $formsBuilder;
    private $resourceName;

    public function __construct(string $resourceName, Builder\PathSwitchBuilder $formsBuilder)
    {
        $this->resourceName = $resourceName;
        $this->formsBuilder = $formsBuilder;
    }

    public function formSwitch(string $id): Builder\RouteScanBuilder
    {
        $routeWrapper = function (Route $route) use ($id) {
            return new Route\Gate\UriAttributeSelect($route, $id, 'edit', 'new');
        };

        return $this->formsBuilder
                    ->route($this->resourceName)
                    ->wrapRouteCallback($routeWrapper)
                    ->responseScan();
    }
}
