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


class ResponseScanSwitch implements Route
{
    use RouteSelectMethods;

    protected $routes = [];

    /**
     * @param Route[] $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $response = $prototype;
        foreach ($this->routes as $route) {
            $response = $route->forward($request, $prototype);
            if ($response !== $prototype) { break; }
        }

        return $response;
    }

    public function select(string $path): Route
    {
        [$id, $path] = $this->splitPath($path);
        return $this->getRoute($id, $path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        throw new EndpointCallException('Cannot resolve specific Uri for switch route');
    }
}
