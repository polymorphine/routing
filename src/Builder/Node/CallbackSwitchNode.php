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

use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Route;
use InvalidArgumentException;


/**
 * Builder Node creating and configuring CallbackSwitch route splitter.
 *
 * @see \Polymorphine\Routing\Route\Splitter\CallbackSwitch
 */
class CallbackSwitchNode implements Node
{
    use CompositeBuilderMethods;

    private $idCallback;

    /**
     * @param Context  $context
     * @param callable $idCallback fn(ServerRequestInterface) => string
     * @param array    $routes
     */
    public function __construct(Context $context, callable $idCallback, array $routes = [])
    {
        $this->context    = $context;
        $this->idCallback = $idCallback;
        $this->routes     = $routes;
    }

    /**
     * Creates builder context for route accessible through $name
     * identifier returned from request processing callback.
     *
     * @param string $name
     *
     * @return RouteNode
     */
    public function route(string $name): RouteNode
    {
        if (!$name) {
            throw new InvalidArgumentException('Name is required for callback route switch');
        }

        return $this->addBuilder($name);
    }

    protected function router(array $routes): Route
    {
        return new Route\Splitter\CallbackSwitch($routes, $this->idCallback);
    }
}
