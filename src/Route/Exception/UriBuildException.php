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

use InvalidArgumentException;


class UriBuildException extends InvalidArgumentException
{
    /**
     * @param string $path Called route path to display in message
     *
     * @return static
     */
    public function withPathInfo(string $path): self
    {
        return new static($this->message . ' (called route: ' . $path . ')');
    }
}
