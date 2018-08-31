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
 * Builder that creates linear endpoint Route or given routing tree
 * wrapped by gates in its own (root) context that is not attached to any
 * node builder in composition tree.
 */
class DiscreteRouteBuilder
{
    use GateBuildMethods;

    private $context;

    public function __construct(BuilderContext $context)
    {
        $this->context = $context;
    }

    /**
     * Creates CallbackEndpoint with given callback.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\CallbackEndpoint
     *
     * @param callable $callback takes ServerRequestInterface parameter and returns ResponseInterface
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
     * @param callable $routeCallback takes no parameter and returns Route instance
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
     * depends on - otherwise BuilderLogicException will be thrown.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\RedirectEndpoint
     *
     * @param string $routingPath
     * @param int    $code
     *
     * @throws Exception\BuilderLogicException
     *
     * @return Route
     */
    public function redirect(string $routingPath, int $code = 301): Route
    {
        $this->context->setRedirectRoute($routingPath, $code);
        return $this->context->build();
    }

    /**
     * Creates HandlerFactoryEndpoint with given Fully Qualified Name
     * of the class that implements RequestHandlerFactory.
     *
     * To call this method BuilderContext the class was instantiated with
     * needs to be able to provide ContainerInterface that this endpoint
     * depends on - otherwise BuilderLogicException will be thrown.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\HandlerFactoryEndpoint
     *
     * @param string $className FQN of class implementing RequestHandlerFactory
     *
     * @throws Exception\BuilderLogicException
     *
     * @return Route
     */
    public function factory(string $className): Route
    {
        $this->context->setFactoryRoute($className);
        return $this->context->build();
    }

    /**
     * Wraps given Route with called gates.
     *
     * @param Route $route
     *
     * @return Route
     */
    public function join(Route $route): Route
    {
        $this->context->setRoute($route);
        return $this->context->build();
    }
}
