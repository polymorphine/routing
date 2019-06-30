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
use Psr\Http\Server\RequestHandlerInterface;


class Context
{
    private $mappedRoutes;

    /** @var null|Route */
    private $route;

    /** @var null|Node */
    private $builder;

    /** @var callable[] */
    private $gates = [];

    public function __construct(MappedRoutes $mappedRoutes)
    {
        $this->mappedRoutes = $mappedRoutes;
    }

    public function build(): Route
    {
        if ($this->route) { return $this->route; }
        if (!$this->builder) {
            throw new Exception\BuilderLogicException('Route type not selected');
        }
        return $this->route = $this->wrapRoute($this->builder->build());
    }

    public function create(): Context
    {
        $newContext = clone $this;

        $newContext->builder = null;
        $newContext->route   = null;
        $newContext->gates   = [];

        return $newContext;
    }

    /**
     * @param callable $routeWrapper function(Route): Route
     */
    public function addGate(callable $routeWrapper): void
    {
        $this->gates[] = $routeWrapper;
    }

    /**
     * @param callable $callback function(ServerRequestInterface): ResponseInterface
     */
    public function setCallbackRoute(callable $callback): void
    {
        $this->setRoute(new Route\Endpoint\CallbackEndpoint($callback));
    }

    public function setHandlerRoute(RequestHandlerInterface $handler): void
    {
        $this->setRoute(new Route\Endpoint\HandlerEndpoint($handler));
    }

    /**
     * @param callable $routeCallback function(): Route
     */
    public function setLazyRoute(callable $routeCallback): void
    {
        $this->setRoute(new Route\Gate\LazyRoute($routeCallback));
    }

    public function setRedirectRoute(string $routingPath, int $code = 301): void
    {
        $this->setRoute($this->mappedRoutes->redirect($routingPath, $code));
    }

    public function mapEndpoint(string $id): void
    {
        $this->setRoute($this->mappedRoutes->endpoint($id));
    }

    public function mapGate(string $id): void
    {
        $this->addGate($this->mappedRoutes->gateway($id));
    }

    public function setRoute(Route $route): void
    {
        $this->stateCheck();
        $this->route = $this->wrapRoute($route);
    }

    public function setBuilder(Node $builder): void
    {
        $this->stateCheck();
        $this->builder = $builder;
    }

    private function wrapRoute(Route $route): Route
    {
        while ($gate = array_pop($this->gates)) {
            $route = $gate($route);
        }

        return $route;
    }

    private function stateCheck(): void
    {
        if (!$this->route && !$this->builder) { return; }
        throw new Exception\BuilderLogicException('Route already built');
    }
}