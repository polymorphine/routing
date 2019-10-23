<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Map\Exception;

use InvalidArgumentException;
use Polymorphine\Routing\Route\Exception;


class UnreachableEndpointException extends InvalidArgumentException
{
    public static function unexpectedPathSegment(string $routePath): self
    {
        $message = 'URI path for route `%s` is defined as root context of PathSwitch and should not be expanded';
        return new self(sprintf($message, $routePath));
    }

    public static function labelConflict(string $label, string $routePath): self
    {
        $message = 'Unselectable route `%s` on implicit path of `%s` splitter';
        return new self(sprintf($message, $label, $routePath));
    }

    public static function rootLabelConflict(string $rootLabel): self
    {
        $message = 'Root route label `%s` used on root level splitter';
        return new self(sprintf($message, $rootLabel));
    }

    public static function uriConflict(Exception\InvalidUriPrototypeException $e, string $routePath): self
    {
        $message = $e->getMessage() . ' (route: `%s`)';
        return new self(sprintf($message, $routePath));
    }
}
