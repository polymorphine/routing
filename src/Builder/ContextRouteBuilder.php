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

use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Route;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Builder that adds Route or given routing tree wrapped by gates
 * to current BuilderContext or creates Builder where new contexts
 * Routes are created (branched routes).
 *
 * Current context might be built as root with build() method.
 */
class ContextRouteBuilder implements Builder
{
    use GateBuildMethods;

    private $context;

    public function __construct(BuilderContext $context)
    {
        $this->context = $context;
    }

    public function build(): Route
    {
        return $this->context->build();
    }

    /**
     * Adds CallbackEndpoint created with given callback.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\CallbackEndpoint
     *
     * @param callable $callback function(ServerRequestInterface): ResponseInterface
     */
    public function callback(callable $callback): void
    {
        $this->context->setCallbackRoute($callback);
    }

    /**
     * Adds HandlerEndpoint created with given handler.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\HandlerEndpoint
     *
     * @param RequestHandlerInterface $handler
     */
    public function handler(RequestHandlerInterface $handler): void
    {
        $this->context->setHandlerRoute($handler);
    }

    /**
     * Adds LazyRoute gate that invokes routes with given
     * callback on forward request call.
     *
     * @see \Polymorphine\Routing\Route\Gate\LazyRoute
     *
     * @param callable $routeCallback function(): Route
     */
    public function lazy(callable $routeCallback): void
    {
        $this->context->setLazyRoute($routeCallback);
    }

    /**
     * Adds endpoint that returns redirect response to given routing
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
     */
    public function redirect(string $routingPath, int $code = 301): void
    {
        $this->context->setRedirectRoute($routingPath, $code);
    }

    /**
     * Adds HandlerFactoryEndpoint created with given Fully Qualified Name
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
     */
    public function factory(string $className): void
    {
        $this->context->setFactoryRoute($className);
    }

    /**
     * Adds given Route wrapped with called gates.
     *
     * @param Route $route
     */
    public function join(Route $route): void
    {
        $this->context->setRoute($route);
    }

    /**
     * Adds a link to another Builder context using reference variable.
     * If Route in that context will not be created until this builder
     * will attempt to build it BuilderLogicException will be thrown.
     *
     * @param null|Route &$route reference to current of future Route
     */
    public function joinLink(?Route &$route): void
    {
        $this->context->setBuilder(new LinkedRouteBuilder($route));
    }

    /**
     * Creates PathSwitch node context builder.
     * Optionally already defined array of Routes with keys representing
     * Uri (and routing) path segment might be given as parameter.
     *
     * @see \Polymorphine\Routing\Route\Splitter\PathSwitch
     *
     * @param Route[] $routes associated with Uri & routing path segment keys
     *
     * @return PathSwitchBuilder
     */
    public function pathSwitch(array $routes = []): PathSwitchBuilder
    {
        return $this->contextBuilder(new PathSwitchBuilder($this->context, $routes));
    }

    /**
     * Creates RouteScan node context builder.
     * Optionally already defined array of Routes with (optional) keys
     * representing routing path segment might be given as parameter.
     * Anonymous Routes (without key) cannot be explicitly selected
     * (to produce Uri), but matched request would reach them.
     *
     * @see \Polymorphine\Routing\Route\Splitter\RouteScan
     *
     * @param Route[] $routes associated with routing path segment keys
     *
     * @return RouteScanBuilder
     */
    public function responseScan(array $routes = []): RouteScanBuilder
    {
        return $this->contextBuilder(new RouteScanBuilder($this->context, $routes));
    }

    /**
     * Creates MethodSwitch node context builder.
     * Optionally already defined array of Routes with keys representing
     * http methods (and routing path segment) might be given as parameter.
     *
     * @see \Polymorphine\Routing\Route\Splitter\MethodSwitch
     *
     * @param Route[] $routes associated with http method keys
     *
     * @return MethodSwitchBuilder
     */
    public function methodSwitch(array $routes = []): MethodSwitchBuilder
    {
        return $this->contextBuilder(new MethodSwitchBuilder($this->context, $routes));
    }

    /**
     * Creates node context builder producing composite routing logic
     * for REST resource.
     * Optionally already defined array of Routes with keys representing
     * http methods and pseudo methods* (and routing path segment) might be
     * given as parameter.
     *
     * Pseudo methods are:
     * INDEX - for GET requests to all resources (unspecified id),
     * NEW   - for GET requests to form producing new resource (without current id),
     * EDIT  - for GET requests to form editing resource with given id
     *
     * WARNING: This method does not specify resource name (path) and should be
     * used in case when resource route needs to be wrapped with additional gates.
     * Otherwise it is recommended to build resource with method that defines its
     * name directly in PathSwitchBuilder or RouteScanBuilder.
     *
     * @see RouteScanBuilder::resource()
     * @see PathSwitchBuilder::resource()
     *
     * @param array $routes associated with http method and pseudo method keys
     *
     * @return ResourceSwitchBuilder
     */
    public function resource(array $routes = []): ResourceSwitchBuilder
    {
        return $this->contextBuilder(new ResourceSwitchBuilder($this->context, $routes));
    }

    private function contextBuilder(Builder $builder)
    {
        $this->context->setBuilder($builder);
        return $builder;
    }
}
