<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;


interface RequestHandlerFactory
{
    public function createHandler(ContainerInterface $container): RequestHandlerInterface;
}
