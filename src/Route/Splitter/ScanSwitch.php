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
use Polymorphine\Routing\Exception\EndpointCallException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class ScanSwitch implements Route
{
    use RouteSelectMethods;

    protected $routes = [];
    protected $defaultRoute;

    /**
     * @param Route[] $routes
     * @param Route   $defaultRoute
     */
    public function __construct(array $routes, Route $defaultRoute = null)
    {
        $this->routes       = $routes;
        $this->defaultRoute = $defaultRoute;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $response = $this->checkDefaultRoute($request, $prototype);
        return ($response !== $prototype) ? $response : $this->scanRoutes($request, $prototype);
    }

    public function select(string $path): Route
    {
        [$id, $nextPath] = $this->splitPath($path);

        if ($id && !isset($this->routes[$id]) && isset($this->defaultRoute)) {
            return $this->defaultRoute->select($path);
        }

        return $this->getRoute($id, $nextPath);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if ($this->defaultRoute) {
            return $this->defaultRoute->uri($prototype, $params);
        }

        throw new EndpointCallException('Cannot resolve specific Uri for switch route');
    }

    private function checkDefaultRoute(ServerRequestInterface $request, ResponseInterface $prototype)
    {
        return $this->defaultRoute ? $this->defaultRoute->forward($request, $prototype) : $prototype;
    }

    private function scanRoutes(ServerRequestInterface $request, ResponseInterface $prototype)
    {
        $response = $prototype;
        foreach ($this->routes as $route) {
            $response = $route->forward($request, $prototype);
            if ($response !== $prototype) { break; }
        }

        return $response;
    }
}
