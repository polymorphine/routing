<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception\GatewayCallException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class FirstMatchForwardGateway implements Route
{
    use LockedEndpointMethod;

    private $routes = [];

    /**
     * @param Route[] $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $notFound): ResponseInterface
    {
        $response = $notFound;
        foreach ($this->routes as $route) {
            $response = $route->forward($request, $notFound);
            if ($response !== $notFound) { break; }
        }

        return $response;
    }

    public function gateway(string $path): Route
    {
        [$id, $path] = explode(self::PATH_SEPARATOR, $path, 2) + [false, false];

        if (!$id) {
            throw new GatewayCallException('Invalid gateway path - non empty string required');
        }

        if (!isset($this->routes[$id])) {
            throw new GatewayCallException(sprintf('Gateway `%s` not found', $id));
        }

        return $path ? $this->route($this->routes[$id], $path) : $this->routes[$id];
    }

    private function route(Route $route, string $path)
    {
        return $route->gateway($path);
    }
}
