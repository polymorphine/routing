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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * The purpose of this class is to provide router callback for
 * redirect endpoints (that will use router's uri at runtime)
 * and procedures resolving gate and endpoint identifiers by
 * user defined convention.
 */
class MappedRoutes
{
    private $endpoint;
    private $gateway;
    private $router;

    /**
     * Supplied convention will be used throughout entire router build
     * process and cannot be changed (even if mutable router callback
     * is redefined only last variant will be used).
     *
     * If there's a need for more endpoint and gate resolvers of this
     * kind, you might differentiate them using some identifier parsing
     * strategies (like prefix based selection).
     *
     * Static constructor using ContainerInterface is only suggestion,
     * that also serves as more detailed example of main constructor
     * parameters.
     *
     * @param callable|null $router   fn() => Router
     * @param callable|null $endpoint fn(string) => Endpoint|Route
     * @param callable|null $gateway  fn(string, Route) => Route
     */
    public function __construct(?callable $router, ?callable $endpoint, ?callable $gateway)
    {
        $this->router   = $router;
        $this->endpoint = $endpoint;
        $this->gateway  = $gateway;
    }

    /**
     * Creates container based convention for endpoint and gate mapping.
     *
     * Endpoint will resolve class name (FQN) as RequestHandlerInterface
     * factory instantiated with container parameter, creating handler
     * instance using request headers.
     *
     * Gate will attempt to get MiddlewareInterface from container, and
     * create MiddlewareRoute with it.
     *
     * @param ContainerInterface $container
     *
     * @return static
     */
    public static function withContainerMapping(ContainerInterface $container): self
    {
        $endpoint = function (string $class) use ($container): Route\Endpoint {
            return new Route\Endpoint\CallbackEndpoint(
                function (ServerRequestInterface $request) use ($class, $container) {
                    /** @var object $factory */
                    $factory = new $class($container);
                    /** @var RequestHandlerInterface $handler */
                    $handler = $factory->create($request->getHeaders());
                    return $handler->handle($request);
                }
            );
        };

        $gate = function ($middleware, Route $route) use ($container): Route {
            return new Route\Gate\LazyRoute(function () use ($middleware, $container, $route) {
                return new Route\Gate\MiddlewareGateway($container->get($middleware), $route);
            });
        };

        return new self(null, $endpoint, $gate);
    }

    /**
     * @return bool Whether Router callback was defined
     */
    public function hasRouterCallback(): bool
    {
        return isset($this->router);
    }

    /**
     * @param callable $router fn() => Router
     *
     * @return static
     */
    public function withRouterCallback(callable $router): self
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @param string $path Routing path this Route should redirect to
     * @param int    $code Http redirect Response code (preferably 3xx)
     *
     * @return Route
     */
    public function redirect(string $path, int $code): Route
    {
        if (!$this->router) {
            throw Exception\ConfigException::requiredRouterCallback($path);
        }

        return new Route\Endpoint\RedirectEndpoint(function () use ($path) {
            return (string) ($this->router)()->uri($path);
        }, $code);
    }

    /**
     * @param string $id
     *
     * @return Route Endpoint Route resolved from given $id
     */
    public function endpoint(string $id): Route
    {
        if (!$this->endpoint) {
            throw Exception\ConfigException::requiredEndpointMapping($id);
        }

        return ($this->endpoint)($id);
    }

    /**
     * Method resolves given $id to Route gateway and returns
     * callback able to wrap passed Route with that gateway.
     *
     * @param string $id
     *
     * @return callable fn(Route) => Route
     *                  Gateway wrapper to given Route
     */
    public function gateway(string $id): callable
    {
        if (!$this->gateway) {
            throw Exception\ConfigException::requiredGatewayMapping($id);
        }

        return function (Route $route) use ($id): Route {
            return ($this->gateway)($id, $route);
        };
    }
}
