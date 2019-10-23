<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Exception;

use RuntimeException;


class UndefinedUriException extends RuntimeException
{
    public static function forSwitchContext(): self
    {
        return new self('Cannot create distinct URI for switch route');
    }

    public function withPathInfo(string $path): self
    {
        return new self($this->message . ' (called route: ' . $path . ')');
    }
}
