<?php declare(strict_types=1);

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
    private MappedRoutes $mappedRoutes;

    private ?Route $route   = null;
    private ?Node  $builder = null;

    /** @var callable[] fn(Route) => Route */
    private array $gates = [];

    /**
     * @param MappedRoutes $mappedRoutes
     */
    public function __construct(MappedRoutes $mappedRoutes)
    {
        $this->mappedRoutes = $mappedRoutes;
    }

    /**
     * @return Route
     */
    public function build(): Route
    {
        if ($this->route) { return $this->route; }
        if (!$this->builder) {
            throw Exception\BuilderLogicException::incompleteRouteDefinition();
        }
        return $this->route = $this->wrapRoute($this->builder->build());
    }

    /**
     * @return Context
     */
    public function create(): Context
    {
        $newContext = clone $this;

        $newContext->builder = null;
        $newContext->route   = null;
        $newContext->gates   = [];

        return $newContext;
    }

    /**
     * @param callable $routeWrapper fn(Route) => Route
     */
    public function addGate(callable $routeWrapper): void
    {
        $this->gates[] = $routeWrapper;
    }

    /**
     * @param callable $callback fn(ServerRequestInterface) => ResponseInterface
     */
    public function setCallbackRoute(callable $callback): void
    {
        $this->setRoute(new Route\Endpoint\CallbackEndpoint($callback));
    }

    /**
     * @param RequestHandlerInterface $handler
     */
    public function setHandlerRoute(RequestHandlerInterface $handler): void
    {
        $this->setRoute(new Route\Endpoint\HandlerEndpoint($handler));
    }

    /**
     * @param callable $routeCallback fn() => Route
     */
    public function setLazyRoute(callable $routeCallback): void
    {
        $this->setRoute(new Route\Gate\LazyRoute($routeCallback));
    }

    /**
     * @param string $routingPath
     * @param int    $code
     *
     * @throws Exception\ConfigException
     */
    public function setRedirectRoute(string $routingPath, int $code = 301): void
    {
        $this->setRoute($this->mappedRoutes->redirect($routingPath, $code));
    }

    /**
     * @param string $id
     *
     * @throws Exception\ConfigException
     */
    public function mapEndpoint(string $id): void
    {
        $this->setRoute($this->mappedRoutes->endpoint($id));
    }

    /**
     * @param string $id
     *
     * @throws Exception\ConfigException
     */
    public function mapGate(string $id): void
    {
        $this->addGate($this->mappedRoutes->gateway($id));
    }

    /**
     * @param Route $route
     */
    public function setRoute(Route $route): void
    {
        $this->stateCheck();
        $this->route = $this->wrapRoute($route);
    }

    /**
     * @param Node $builder
     */
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
        throw Exception\BuilderLogicException::contextRouteAlreadyDefined();
    }
}
