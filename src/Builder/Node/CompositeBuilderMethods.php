<?php declare(strict_types=1);

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
use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Builder\Exception;


trait CompositeBuilderMethods
{
    protected Route   $switch;
    protected Context $context;
    protected array   $builders = [];
    protected array   $routes   = [];

    /**
     * @return Route
     */
    public function build(): Route
    {
        return $this->switch ??= $this->newSwitchInstance();
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
            throw Exception\BuilderLogicException::routeNameAlreadyDefined($name);
        }

        return $name;
    }

    private function newSwitchInstance(): Route
    {
        foreach ($this->builders as $name => $builder) {
            $this->routes[$name] = $builder->build();
        }

        return $this->router($this->routes);
    }
}
