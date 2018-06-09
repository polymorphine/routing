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

use Polymorphine\Routing\Exception\EndpointCallException;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception\SwitchCallException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class ResponseScanSwitch implements Route
{
    private $routes = [];

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

    public function route(string $path): Route
    {
        [$id, $path] = explode(self::PATH_SEPARATOR, $path, 2) + [false, false];

        if (!$id) {
            throw new SwitchCallException('Invalid gateway path - non empty string required');
        }

        if (!isset($this->routes[$id])) {
            throw new SwitchCallException(sprintf('Gateway `%s` not found', $id));
        }

        return $path ? $this->switchRoute($this->routes[$id], $path) : $this->routes[$id];
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        throw new EndpointCallException('Uri not defined in gateway route');
    }

    private function switchRoute(Route $route, string $path)
    {
        return $route->route($path);
    }
}
