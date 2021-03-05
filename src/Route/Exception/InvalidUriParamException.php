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


class InvalidUriParamException extends UriBuildException
{
    public static function missingParam(string $name): self
    {
        $message = 'Missing `%s` parameter';
        return new self(sprintf($message, $name));
    }

    public static function formatMismatch(string $name, string $pattern): self
    {
        $message = 'Invalid `%s` parameter format - expected value matching `%s` pattern';
        return new self(sprintf($message, $name, $pattern));
    }
}
