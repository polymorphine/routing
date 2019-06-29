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

use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Builder\MappedRoutes;
use Psr\Container\ContainerInterface;


trait ContextCreateMethod
{
    private function context(?ContainerInterface $container = null, ?callable $router = null): Context
    {
        $router = $router ?: function () {};
        return $container
            ? new Context(MappedRoutes::withContainerMapping($container)->withRouterCallback($router))
            : new Context(new MappedRoutes($router, null, null));
    }
}
