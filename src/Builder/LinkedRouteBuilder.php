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


class LinkedRouteBuilder implements Builder
{
    private $futureRoute;

    public function __construct(&$futureRoute)
    {
        $this->futureRoute = &$futureRoute;
    }

    public function build(): Route
    {
        if (!$this->futureRoute instanceof Route) {
            throw new Exception\BuilderLogicException('Linked Route not built (check for circular reference)');
        }

        return $this->futureRoute;
    }
}
