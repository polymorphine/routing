<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\RouteHandler;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class MiddlewareGateway implements Route
{
    private $middleware;
    private $route;

    public function __construct(MiddlewareInterface $middleware, Route $route)
    {
        $this->middleware = $middleware;
        $this->route      = $route;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $notFound): ResponseInterface
    {
        return $this->middleware->process($request, new RouteHandler($this->route, $notFound));
    }

    public function gateway(string $path): Route
    {
        return $this->route->gateway($path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->route->uri($prototype, $params);
    }
}
