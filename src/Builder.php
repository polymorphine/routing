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
    private $baseUri;
    private $nullResponse;

    /** @var ContainerInterface */
    private $container;

    /** @var Node */
    private $builder;

    /** @var Router */
    private $router;

    public function __construct(
        UriInterface $baseUri,
        ResponseInterface $nullResponse,
        ContainerInterface $container = null
    ) {
        $this->baseUri      = $baseUri;
        $this->nullResponse = $nullResponse;
        $this->container    = $container;
    }

    public function router(): Router
    {
        if ($this->router) { return $this->router; }
        if (!$this->builder) {
            throw new Exception\BuilderLogicException('Root builder not defined');
        }
        return $this->router = new Router($this->builder->build(), $this->baseUri, $this->nullResponse);
    }

    public function rootNode(): Node\ContextRouteNode
    {
        if ($this->builder) {
            throw new Exception\BuilderLogicException('Root builder already defined');
        }
        $this->builder = $this->createContext();
        return new Node\ContextRouteNode($this->builder);
    }

    public function detachedNode(): Node\ContextRouteNode
    {
        return new Node\ContextRouteNode($this->createContext());
    }

    public function route(): Builder\DiscreteRouteBuilder
    {
        return new Builder\DiscreteRouteBuilder($this->createContext());
    }

    private function createContext(): Builder\NodeContext
    {
        return new Builder\NodeContext($this->container, function () { return $this->router; });
    }
}
