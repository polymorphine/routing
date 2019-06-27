<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Node\Resource;

use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Route;


class FormsContext
{
    private $formsBuilder;
    private $resourceName;

    public function __construct(string $resourceName, Node\PathSwitchNode $formsBuilder)
    {
        $this->resourceName = $resourceName;
        $this->formsBuilder = $formsBuilder;
    }

    public function builder(string $id): Node\RouteScanNode
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