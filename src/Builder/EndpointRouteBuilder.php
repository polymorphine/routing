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


/**
 * Builder that creates linear endpoint Route (or is attached to another
 * routing tree) wrapped by gates in its own context that is separate to
 * builder composition.
 *
 * Serves as a convenience method of composing object consisting of gate
 * routes and Endpoint classes.
 */
class EndpointRouteBuilder
{
    use Node\GateBuildMethods;

    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Creates CallbackEndpoint with given callback.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\CallbackEndpoint
     *
     * @param callable $callback fn(ServerRequestInterface) => ResponseInterface
     *
     * @return Route
     */
    public function callback(callable $callback): Route
    {
        $this->context->setCallbackRoute($callback);
        return $this->context->build();
    }

    /**
     * Creates HandlerEndpoint with given handler.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\HandlerEndpoint
     *
     * @param RequestHandlerInterface $handler
     *
     * @return Route
     */
    public function handler(RequestHandlerInterface $handler): Route
    {
        $this->context->setHandlerRoute($handler);
        return $this->context->build();
    }

    /**
     * Creates LazyRoute gate that invokes routes with given
     * callback on forward request call.
     *
     * @see \Polymorphine\Routing\Route\Gate\LazyRoute
     *
     * @param callable $routeCallback fn() => Route
     *
     * @return Route
     */
    public function lazy(callable $routeCallback): Route
    {
        $this->context->setLazyRoute($routeCallback);
        return $this->context->build();
    }

    /**
     * Creates endpoint that returns redirect response to given routing
     * path for any request being forwarded.
     *
     * To call this method BuilderContext the class was instantiated with
     * needs to be able to provide Router callback that this endpoint
     * depends on - otherwise ConfigException will be thrown.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\RedirectEndpoint
     * @see \Polymorphine\Routing\Builder\MappedRoutes::redirect()
     *
     * @param string $routingPath
     * @param int    $code
     *
     * @throws Exception\ConfigException
     *
     * @return Route
     */
    public function redirect(string $routingPath, int $code = 301): Route
    {
        $this->context->setRedirectRoute($routingPath, $code);
        return $this->context->build();
    }

    /**
     * Creates endpoint Route by resolving passed identifier.
     *
     * NOTE: In order to use this method endpoint callback in
     * MappedRoutes has to be defined or ConfigException will
     * be thrown.
     *
     * @see \Polymorphine\Routing\Builder\MappedRoutes::endpoint()
     *
     * @param string $id
     *
     * @throws Exception\ConfigException
     *
     * @return Route
     */
    public function endpointId(string $id): Route
    {
        $this->context->mapEndpoint($id);
        return $this->context->build();
    }

    /**
     * Wraps given Route with called gates.
     *
     * @param Route $route
     *
     * @return Route
     */
    public function joinRoute(Route $route): Route
    {
        $this->context->setRoute($route);
        return $this->context->build();
    }
}
