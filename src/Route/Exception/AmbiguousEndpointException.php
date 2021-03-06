<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Exception;


class AmbiguousEndpointException extends UriBuildException
{
    public static function forSwitchContext(): self
    {
        return new self('Cannot create distinct URI for switch route');
    }
}
