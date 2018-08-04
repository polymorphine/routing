<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Builder;
use InvalidArgumentException;


abstract class SwitchBuilder implements Builder
{
    /** @var Builder[] */
    protected $builders = [];
    protected $context;

    public function __construct(RouteBuilder $context = null)
    {
        $this->context = $context ?? new RouteBuilder();
    }

    public function build(): Route
    {
        $routes = [];
        foreach ($this->builders as $name => $builder) {
            $routes[$name] = $builder->build();
        }

        return $this->router($routes);
    }

    public function route(string $name = null): RouteBuilder
    {
        return $this->addBuilder($this->context->route(), $name);
    }

    abstract protected function router(array $routes): Route;

    protected function addBuilder(RouteBuilder $builder, ?string $name): RouteBuilder
    {
        return $name ? $this->builders[$this->validName($name)] = $builder : $this->builders[] = $builder;
    }

    protected function validName(string $name): string
    {
        if (!isset($this->builders[$name])) { return $name; }

        $message = 'Route name `%s` already exists in this scope';
        throw new InvalidArgumentException(sprintf($message, $name));
    }
}
