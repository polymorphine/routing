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
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;


class EndpointSetup
{
    use GateBuildMethods;
    use EndpointBuilderMethods;

    private $container;
    private $routerCallback;

    /**
     * @param null|ContainerInterface $container      required to build endpoint using factory() method
     * @param null|callable           $routerCallback should return Router instance and is required
     *                                                only to build endpoint with redirect() method
     */
    public function __construct(?ContainerInterface $container = null, ?callable $routerCallback = null)
    {
        $this->container      = $container;
        $this->routerCallback = $routerCallback;
    }

    /**
     * Creates wrapped CallbackEndpoint with given callback.
     * Callback called with ServerRequestInterface parameter
     * must return ResponseInterface.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\CallbackEndpoint
     *
     * @param callable $callback
     *
     * @return Route
     */
    public function callback(callable $callback): Route
    {
        return $this->wrapCallbackRoute($callback);
    }

    /**
     * Creates wrapped HandlerEndpoint with given handler.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\HandlerEndpoint
     *
     * @param RequestHandlerInterface $handler
     *
     * @return Route
     */
    public function handler(RequestHandlerInterface $handler): Route
    {
        return $this->wrapHandlerRoute($handler);
    }

    /**
     * Returns given Route wrapped with previously called gates.
     *
     * @param Route $route
     *
     * @return Route
     */
    public function join(Route $route): Route
    {
        return $this->wrapJoinedRoute($route);
    }

    /**
     * Creates wrapped LazyRoute gate that invokes routes with given
     * callback on forward request call.
     *
     * @see \Polymorphine\Routing\Route\Gate\LazyRoute
     *
     * @param callable $routeCallback Callback that returns Route instance
     *
     * @return Route
     */
    public function lazy(callable $routeCallback): Route
    {
        return $this->wrapLazyRoute($routeCallback);
    }

    /**
     * Creates wrapped endpoint that returns redirect response to given
     * routing path for any request being forwarded.
     *
     * To call this method EndpointSetup needs to be instantiated with
     * Router callback dependency.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\RedirectEndpoint
     *
     * @param string $routePath
     * @param int    $code
     *
     * @return Route
     */
    public function redirect(string $routePath, int $code = 301): Route
    {
        return $this->wrapRedirectRoute($routePath, $code);
    }

    /**
     * Creates wrapped HandlerFactoryEndpoint with given Fully Qualified
     * Name of class implementing RequestHandlerFactory.
     *
     * To call this method EndpointSetup needs to be instantiated with
     * ContainerInterface dependency.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\HandlerFactoryEndpoint
     *
     * @param string $className
     *
     * @return Route
     */
    public function factory(string $className): Route
    {
        return $this->wrapFactoryRoute($className);
    }
}
