<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate;

use Polymorphine\Routing\Route;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


/**
 * Gate route chaining MiddlewareInterface processing within
 * routing execution path.
 */
class MiddlewareGateway implements Route
{
    private $middleware;
    private $route;

    /**
     * @param MiddlewareInterface $middleware
     * @param Route               $route      receives request after processed in middleware
     */
    public function __construct(MiddlewareInterface $middleware, Route $route)
    {
        $this->middleware = $middleware;
        $this->route      = $route;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $this->middleware->process($request, new RouteHandler($this->route, $prototype));
    }

    public function select(string $path): Route
    {
        return $this->route->select($path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->route->uri($prototype, $params);
    }

    public function routes(string $path, UriInterface $uri): array
    {
        // TODO: Implement routes() method.
        return [];
    }
}
