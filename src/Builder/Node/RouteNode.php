<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Node;

use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Builder\Exception;
use Polymorphine\Routing\Builder\Node\Resource\ResourceSwitchNode;
use Polymorphine\Routing\Builder\Node\Resource\LinkedFormsResourceSwitchNode;
use Polymorphine\Routing\Builder\Node\Resource\FormsContext;
use Polymorphine\Routing\Route;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Builder that adds Route or given routing tree wrapped by gates
 * to current NodeContext or creates Builder where new contexts
 * Routes are created (branched routes).
 *
 * Current context might be built as root with build() method.
 */
class RouteNode implements Node
{
    use GateBuildMethods;

    private $context;

    public function __construct(Context $context)
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
     * depends on - otherwise ConfigException will be thrown.
     *
     * @see \Polymorphine\Routing\Route\Endpoint\RedirectEndpoint
     * @see \Polymorphine\Routing\Builder\MappedRoutes::redirect()
     *
     * @param string $routingPath
     * @param int    $code
     *
     * @throws Exception\ConfigException
     */
    public function redirect(string $routingPath, int $code = 301): void
    {
        $this->context->setRedirectRoute($routingPath, $code);
    }

    /**
     * Adds endpoint Route resolved from passed identifier.
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
     */
    public function endpoint(string $id): void
    {
        $this->context->mapEndpoint($id);
    }

    /**
     * Adds given Route wrapped with called gates.
     *
     * @param Route $route
     */
    public function joinRoute(Route $route): void
    {
        $this->context->setRoute($route);
    }

    /**
     * Adds a link to another Builder context using reference variable.
     * If Route in that context will not be created until this builder
     * will attempt to build it BuilderLogicException will be thrown.
     *
     * @param Route|null &$route reference to current of future Route
     */
    public function joinLink(?Route &$route): void
    {
        $this->context->setBuilder(new LinkedRouteNode($route));
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
     * @return PathSwitchNode
     */
    public function pathSwitch(array $routes = []): PathSwitchNode
    {
        return $this->contextBuilder(new PathSwitchNode($this->context, $routes));
    }

    /**
     * Creates RouteScan node context builder.
     * Optionally already defined array of Routes with (optional) keys
     * representing routing path segment might be given as parameter.
     * Anonymous Routes (without key) cannot be explicitly selected
     * (to produce Uri), but matched request would reach them.
     *
     * @see \Polymorphine\Routing\Route\Splitter\ScanSwitch
     *
     * @param Route[] $routes associated with routing path segment keys
     *
     * @return ScanSwitchNode
     */
    public function responseScan(array $routes = []): ScanSwitchNode
    {
        return $this->contextBuilder(new ScanSwitchNode($this->context, $routes));
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
     * @return MethodSwitchNode
     */
    public function methodSwitch(array $routes = []): MethodSwitchNode
    {
        return $this->contextBuilder(new MethodSwitchNode($this->context, $routes));
    }

    /**
     * Creates CallbackSwitch node context builder.
     * Optional defined associative array of Routes with id keys
     * returned by callback.
     *
     * @see \Polymorphine\Routing\Route\Splitter\CallbackSwitch
     *
     * @param callable $idCallback function(ServerRequestInterface): string
     * @param array    $routes
     *
     * @return CallbackSwitchNode
     */
    public function callbackSwitch(callable $idCallback, array $routes = []): CallbackSwitchNode
    {
        return $this->contextBuilder(new CallbackSwitchNode($this->context, $idCallback, $routes));
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
     * Optional ResourceFormsBuilder parameter will be used to define separate
     * routing for forms built with add() and edit() methods or passed as array
     * Route parameters with NEW|EDIT keys.
     *
     * WARNING: This method does not specify resource name (path) and should be
     * used in case when resource route needs to be wrapped with additional gates.
     * Otherwise it is recommended to build resource with method that defines its
     * name directly in PathSwitchBuilder or RouteScanBuilder.
     *
     * @see PathSwitchNode::resource()
     * @see ScanSwitchNode::resource()
     *
     * @param array             $routes
     * @param FormsContext|null $formsBuilder
     *
     * @return ResourceSwitchNode
     */
    public function resource(array $routes = [], ?FormsContext $formsBuilder = null): ResourceSwitchNode
    {
        return $this->contextBuilder(
            $formsBuilder
            ? new LinkedFormsResourceSwitchNode($formsBuilder, $this->context, $routes)
            : new ResourceSwitchNode($this->context, $routes)
        );
    }

    private function contextBuilder(Node $builder)
    {
        $this->context->setBuilder($builder);
        return $builder;
    }
}
