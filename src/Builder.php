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

use Polymorphine\Routing\Builder\MappedRoutes;
use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Builder\Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class Builder
{
    /** @var MappedRoutes */
    private $mappedRoutes;

    /** @var Node */
    private $builder;

    /** @var Router */
    private $router;

    public function __construct(MappedRoutes $mappedRoutes = null)
    {
        $routerCallback = function () { return $this->router; };
        if ($mappedRoutes && !$mappedRoutes->hasRouterCallback()) {
            $this->mappedRoutes = $mappedRoutes->withRouterCallback($routerCallback);
        } else {
            $this->mappedRoutes = $mappedRoutes ?? new MappedRoutes($routerCallback, null, null);
        }
    }

    public static function withContainer(ContainerInterface $container): self
    {
        return new self(MappedRoutes::withContainerMapping($container));
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
        return $this->builder = new Node\RouteNode(new Context($this->mappedRoutes));
    }

    public function detachedNode(): Node\RouteNode
    {
        return new Node\RouteNode(new Context($this->mappedRoutes));
    }

    public function route(): Builder\EndpointRouteBuilder
    {
        return new Builder\EndpointRouteBuilder(new Context($this->mappedRoutes));
    }
}
