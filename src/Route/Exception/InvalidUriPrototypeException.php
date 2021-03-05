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

use Psr\Http\Message\UriInterface;


class InvalidUriPrototypeException extends UriBuildException
{
    public static function patternConflict(string $part, string $value, UriInterface $prototype): self
    {
        $message = 'Applied %s pattern `%s` would overwrite already built `%s` URI';
        return new self(sprintf($message, $part, $value, (string) $prototype));
    }

    public static function segmentConflict(string $value, string $prototypeValue, UriInterface $prototype): self
    {
        $message = 'Applied pattern value `%s` would overwrite `%s` part in `%s` URI';
        return new self(sprintf($message, $value, $prototypeValue, (string) $prototype));
    }

    public static function domainConflict(string $value, UriInterface $prototype): self
    {
        return self::patternConflict('domain', $value, $prototype);
    }

    public static function queryConflict(string $name, string $prototypeValue, string $value)
    {
        $message = 'Uri build conflict - attempt to overwrite `%s` query param value `%s` with `%s`';
        return new self(sprintf($message, $name, $prototypeValue, $value));
    }

    public static function missingHost(string $subdomain)
    {
        $message = 'Cannot attach `%s` subdomain to prototype without host';
        return new self(sprintf($message, $subdomain));
    }
}
