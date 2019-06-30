<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Node;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Builder\Exception;


trait CompositeBuilderMethods
{
    /** @var Node[] */
    protected $builders = [];

    /** @var Route[] */
    protected $routes = [];

    /** @var Route */
    protected $switch;

    /** @var Context */
    protected $context;

    public function build(): Route
    {
        if ($this->switch) { return $this->switch; }

        foreach ($this->builders as $name => $builder) {
            $this->routes[$name] = $builder->build();
        }

        return $this->switch = $this->router($this->routes);
    }

    abstract protected function router(array $routes): Route;

    private function addBuilder(?string $name): RouteNode
    {
        $context = $this->context->create();

        if ($name) {
            $this->builders[$this->validName($name)] = $context;
        } else {
            $this->builders[] = $context;
        }

        return new RouteNode($context);
    }

    private function validName(string $name): string
    {
        if (isset($this->builders[$name]) || isset($this->routes[$name])) {
            $message = 'Route name `%s` already exists in this scope';
            throw new Exception\BuilderLogicException(sprintf($message, $name));
        }

        return $name;
    }
}
