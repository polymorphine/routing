<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Exception;


class ConfigException extends BuilderLogicException
{
    public static function requiredRouterCallback(string $id): self
    {
        $message = 'Required router callback to build redirect route for `%s` identifier';
        return new self(sprintf($message, $id));
    }

    public static function requiredEndpointMapping(string $id): self
    {
        $message = 'Required endpoint mapping callback to build endpoint for `%s` identifier';
        return new self(sprintf($message, $id));
    }

    public static function requiredGatewayMapping(string $id): self
    {
        $message = 'Required gate mapping callback to build middleware for `%s` identifier';
        return new self(sprintf($message, $id));
    }
}
