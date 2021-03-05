<?php declare(strict_types=1);

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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UriFactoryInterface;


class Builder
{
    private ?MappedRoutes $mappedRoutes;
    private ?Node         $builder = null;
    private ?Router       $router  = null;

    /**
     * If object is instantiated without $mappedRoutes parameter
     * it will be created internally with its own router callback,
     * and methods using mapping procedures will be later inaccessible
     * (Exception\BuilderLogicException).
     *
     * Also if provided MappedRoutes instance does not define router
     * callback it will be set by this class.
     *
     * @param MappedRoutes|null $mappedRoutes
     */
    public function __construct(MappedRoutes $mappedRoutes = null)
    {
        $routerCallback = fn () => $this->router;
        if ($mappedRoutes && !$mappedRoutes->hasRouterCallback()) {
            $this->mappedRoutes = $mappedRoutes->withRouterCallback($routerCallback);
        } else {
            $this->mappedRoutes = $mappedRoutes ?? new MappedRoutes($routerCallback, null, null);
        }
    }

    /**
     * Produces Builder with predefined configuration for mapping
     * endpoints and gates using string identifier.
     *
     * @see MappedRoutes
     *
     * @param ContainerInterface $container
     *
     * @return Builder
     */
    public static function withContainer(ContainerInterface $container): self
    {
        return new self(MappedRoutes::withContainerMapping($container));
    }

    /**
     * Creates Router instance based on builder nodes defined on root node.
     *
     * Throws BuilderLogicException if routes are not (fully) defined
     * and builder nodes cannot be resolved into Route instances.
     *
     * @param UriInterface      $baseUri      prototype for uri instances produced with
     *                                        Router::uri() method
     * @param ResponseInterface $nullResponse recognized by Router as a product of unprocessed
     *                                        request (from Router::handle() method) or a base for
     *                                        creating response within one of its Routes
     * @param string|null       $rootPath     routing path selecting root route
     *
     * @throws Exception\BuilderLogicException
     *
     * @return Router
     */
    public function router(
        UriInterface $baseUri,
        ResponseInterface $nullResponse,
        string $rootPath = 'ROOT'
    ): Router {
        if (!$this->builder) {
            throw Exception\BuilderLogicException::undefinedRootContext();
        }
        return $this->router = new Router($this->builder->build(), $baseUri, $nullResponse, $rootPath);
    }

    /**
     * Creates Router instance based on builder nodes defined on root node.
     * Uses PSR-17 factories to create base URI and null response.
     *
     * @param UriFactoryInterface      $uriFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param string|null              $rootPath        routing path selecting root route
     *
     * @throws Exception\BuilderLogicException
     *
     * @return Router
     */
    public function routerWithFactories(
        UriFactoryInterface $uriFactory,
        ResponseFactoryInterface $responseFactory,
        string $rootPath = 'ROOT'
    ): Router {
        return $this->router($uriFactory->createUri(), $responseFactory->createResponse(404), $rootPath);
    }

    /**
     * Returns builder node that produces complex Route as entry point
     * for Router built with router() method.
     *
     * @return Node\RouteNode
     */
    public function rootNode(): Node\RouteNode
    {
        if ($this->builder) {
            throw Exception\BuilderLogicException::rootContextAlreadyDefined();
        }
        return $this->builder = new Node\RouteNode(new Context($this->mappedRoutes));
    }

    /**
     * Creates builder node that can produce complex Route instance
     * just as one used by Router builder (built on root node), but
     * itself is not connected to any of Router's nodes (unless you
     * connect this route passing it as a parameter to one of router
     * node methods).
     *
     * Throws BuilderLogicException if routes are not (fully) defined
     * and builder nodes cannot be resolved into Route instances.
     *
     * NOTE: Redirect endpoint uses Router callback, so if router is
     * not built with this Builder class and you want to use redirect
     * for detached route only make sure you provide valid router
     * callback within MappedRoutes constructor argument.
     *
     * @throws Exception\BuilderLogicException
     *
     * @return Node\RouteNode
     */
    public function detachedNode(): Node\RouteNode
    {
        return new Node\RouteNode(new Context($this->mappedRoutes));
    }

    /**
     * Creates builder that can produce single, linear Route.
     * This route can use various gates and will normally lead
     * to some endpoint, but can also connect other, more
     * complex routes.
     *
     * @return Builder\EndpointRouteBuilder
     */
    public function route(): Builder\EndpointRouteBuilder
    {
        return new Builder\EndpointRouteBuilder(new Context($this->mappedRoutes));
    }
}
