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

use LogicException;


class BuilderLogicException extends LogicException
{
    public static function routeNameAlreadyDefined(string $name): self
    {
        $message = 'Route name `%s` already defined in this scope';
        return new self(sprintf($message, $name));
    }

    public static function rootPathRouteAlreadyDefined(): self
    {
        return new self('Root path route already defined');
    }

    public static function defaultRouteAlreadyDefined(): self
    {
        return new self('Default route already defined');
    }

    public static function resourceFormsAlreadySet(): self
    {
        return new self('Route path for resource forms already defined');
    }

    public static function undefinedRootContext(): self
    {
        return new self('Root builder context not defined');
    }

    public static function rootContextAlreadyDefined(): self
    {
        return new self('Root builder context already defined');
    }

    public static function uriPatternKeywordConflict(string $idName): self
    {
        $message = 'Uri keyword conflict: `resource/new/form` matches `resource/{%s}/form` path';
        return new self(sprintf($message, $idName));
    }

    public static function incompleteRouteDefinition(): self
    {
        return new self('Cannot build node context without route definition');
    }

    public static function contextRouteAlreadyDefined(): self
    {
        return new self('Context route already defined in this node');
    }

    public static function unresolvedLinkedRoute(): self
    {
        return new self('Linked route not built (check for backward reference)');
    }
}
