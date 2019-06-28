<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing;

use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class Builder
{
    /** @var ContainerInterface */
    private $container;

    /** @var Node */
    private $builder;

    /** @var Router */
    private $router;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function router(UriInterface $baseUri, ResponseInterface $nullResponse): Router
    {
        if (!$this->builder) {
            throw new Exception\BuilderLogicException('Root builder not defined');
        }
        return $this->router = new Router($this->builder->build(), $baseUri, $nullResponse);
    }

    public function rootNode(): Node\RouteNode
    {
        if ($this->builder) {
            throw new Exception\BuilderLogicException('Root builder already defined');
        }
        $this->builder = $this->createContext();
        return new Node\RouteNode($this->builder);
    }

    public function detachedNode(): Node\RouteNode
    {
        return new Node\RouteNode($this->createContext());
    }

    public function route(): Builder\DiscreteRouteBuilder
    {
        return new Builder\DiscreteRouteBuilder($this->createContext());
    }

    private function createContext(): Builder\Context
    {
        return new Builder\Context($this->container, function () { return $this->router; });
    }
}
