<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Builder;

use Polymorphine\Routing\Builder;


trait ContextCreateMethod
{
    private function context($container = null, ?callable $router = null): Builder\Context
    {
        $router = $router ?: function () {};
        return $container
            ? new Builder\Context(Builder\MappedRoutes::withContainerMapping($container)->withRouterCallback($router))
            : new Builder\Context(new Builder\MappedRoutes($router, null, null));
    }
}
