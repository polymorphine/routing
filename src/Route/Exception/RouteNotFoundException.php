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

use InvalidArgumentException;


class RouteNotFoundException extends InvalidArgumentException
{
    public static function undefinedGateway(string $name): self
    {
        $message = 'Gateway `%s` not defined';
        return new self(sprintf($message, $name));
    }

    public static function invalidGatewayPath(): self
    {
        return new self('Invalid gateway path - non empty string required');
    }

    public static function unexpectedEndpoint(string $path): self
    {
        $message = 'Endpoint reached - cannot resolve further routing for `%s` path';
        return new self(sprintf($message, $path));
    }

    public function withPathInfo(string $path): self
    {
        return new self($this->message . ' (called route: ' . $path . ')');
    }
}
