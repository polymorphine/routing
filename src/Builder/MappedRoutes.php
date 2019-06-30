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


class MappedRoutes
{
    private $endpoint;
    private $gateway;
    private $router;

    /**
     * The purpose of this class is to provide router callback for
     * redirect endpoints (that will use router's uri at runtime)
     * and procedures resolving gate and endpoint identifiers by
     * user defined convention.
     *
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
     * @param null|callable $router   function(): Router
     * @param null|callable $endpoint function(string): Endpoint|Route
     * @param null|callable $gateway  function(string, Route): Route
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
     * @return MappedRoutes
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

    public function hasRouterCallback(): bool
    {
        return isset($this->router);
    }

    public function withRouterCallback(callable $router): self
    {
        $this->router = $router;
        return $this;
    }

    public function redirect(string $id, int $code): Route
    {
        if (!$this->router) {
            $message = 'Required router callback to build redirect route for `%s` identifier';
            throw new Exception\BuilderLogicException(sprintf($message, $id));
        }

        return new Route\Endpoint\RedirectEndpoint(function () use ($id) {
            return (string) ($this->router)()->uri($id);
        }, $code);
    }

    public function endpoint(string $id): Route
    {
        if (!$this->endpoint) {
            $message = 'Required endpoint mapping to build endpoint for `%s` identifier';
            throw new Exception\BuilderLogicException(sprintf($message, $id));
        }

        return ($this->endpoint)($id);
    }

    public function gateway(string $id): callable
    {
        if (!$this->gateway) {
            $message = 'Required middleware mapping to build middleware for `%s` identifier';
            throw new Exception\BuilderLogicException(sprintf($message, $id));
        }

        return function (Route $route) use ($id): Route {
            return ($this->gateway)($id, $route);
        };
    }
}
