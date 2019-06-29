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
     * @param null|callable $router   function(): Router
     * @param null|callable $endpoint function(string): Route
     * @param null|callable $gateway  function(string, Route): Route
     */
    public function __construct(?callable $router, ?callable $endpoint, ?callable $gateway)
    {
        $this->router   = $router;
        $this->endpoint = $endpoint;
        $this->gateway  = $gateway;
    }

    public static function withContainerMapping(ContainerInterface $container): self
    {
        $endpoint = function (string $class) use ($container): Route {
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
