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


/**
 * Builder context to define form routes accessed with different
 * URI path than REST resource.
 */
class FormsContext
{
    private $formsBuilder;
    private $resourceName;

    /**
     * @param string              $resourceName
     * @param Node\PathSwitchNode $formsBuilder
     */
    public function __construct(string $resourceName, Node\PathSwitchNode $formsBuilder)
    {
        $this->resourceName = $resourceName;
        $this->formsBuilder = $formsBuilder;
    }

    /**
     * @param string $id
     *
     * @return Node\ScanSwitchNode
     */
    public function builder(string $id): Node\ScanSwitchNode
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
