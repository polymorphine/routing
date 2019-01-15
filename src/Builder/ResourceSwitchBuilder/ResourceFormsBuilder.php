<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\ResourceSwitchBuilder;

use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Route;


class ResourceFormsBuilder
{
    private $formsBuilder;
    private $resourceName;
    private $formSwitch;

    public function __construct(string $resourceName, Builder\PathSwitchBuilder $formsBuilder)
    {
        $this->resourceName = $resourceName;
        $this->formsBuilder = $formsBuilder;
    }

    public function formSwitch(string $id): Builder\RouteScanBuilder
    {
        if ($this->formSwitch) { return $this->formSwitch; }

        $routeWrapper = function (Route $route) use ($id) {
            return new Route\Gate\UriAttributeSelect($route, $id, 'edit', 'new');
        };

        return $this->formSwitch = $this->formsBuilder
                                        ->route($this->resourceName)
                                        ->wrapRouteCallback($routeWrapper)
                                        ->responseScan();
    }
}
