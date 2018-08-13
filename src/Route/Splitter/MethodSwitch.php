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
    private $implicit;

    /**
     * @param Route[] $routes   associative array with http method keys (GET, POST, PATCH... etc.)
     * @param string  $implicit
     */
    public function __construct(array $routes, string $implicit = null)
    {
        $this->routes   = $routes;
        $this->implicit = $this->routes[$implicit] ?? null;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $route = $this->routes[$request->getMethod()] ?? null;
        return ($route) ? $route->forward($request, $prototype) : $prototype;
    }

    public function select(string $path): Route
    {
        [$id, $nextPath] = $this->splitPath($path);

        if ($id && !isset($this->routes[$id]) && $this->implicit) {
            return $this->implicit->select($path);
        }
        return $this->getRoute($id, $nextPath);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if ($this->implicit) {
            return $this->implicit->uri($prototype, $params);
        }
        throw new Exception\EndpointCallException('Cannot resolve specific Uri for switch route');
    }
}
