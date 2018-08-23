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


trait EndpointBuilderMethods
{
    public function wrapCallbackRoute(callable $callback): Route
    {
        return $this->wrapGates(new Route\Endpoint\CallbackEndpoint($callback));
    }

    public function wrapHandlerRoute(RequestHandlerInterface $handler): Route
    {
        return $this->wrapGates(new Route\Endpoint\HandlerEndpoint($handler));
    }

    public function wrapJoinedRoute(Route $route): Route
    {
        return $this->wrapGates($route);
    }

    public function wrapLazyRoute(callable $routeCallback): Route
    {
        return $this->wrapGates(new Route\Gate\LazyRoute($routeCallback));
    }

    public function wrapRedirectRoute(string $path, int $code = 301): Route
    {
        if (!$this->routerCallback) {
            throw new Exception\BuilderLogicException('Required container aware builder to build redirect route');
        }

        $uriCallback = function () use ($path) {
            return (string) ($this->routerCallback)()->uri($path);
        };

        return $this->wrapGates(new Route\Endpoint\RedirectEndpoint($uriCallback, $code));
    }

    public function wrapFactoryRoute(string $className): Route
    {
        if (!$this->container) {
            throw new Exception\BuilderLogicException('Required container aware builder to build factory route');
        }

        $factoryCallback = function () use ($className) {
            return new $className();
        };

        return $this->wrapGates(new Route\Endpoint\HandlerFactoryEndpoint($factoryCallback, $this->container));
    }
}
