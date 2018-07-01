<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Splitter;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class MethodSwitch implements Route
{
    use RouteSelectMethods;

    private $routes;

    /**
     * @param Route[] $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $route = $this->routes[$request->getMethod()] ?? null;
        return ($route) ? $route->forward($request, $prototype) : $prototype;
    }

    public function select(string $path): Route
    {
        [$id, $path] = $this->splitPath($path);
        return $this->getRoute($id, $path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        throw new Exception\EndpointCallException('Cannot resolve specific Uri for switch route');
    }
}
