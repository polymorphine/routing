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
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UriInterface;


/**
 * Aggregated Route dispatching incoming requests based on return string
 * from provided callback.
 */
class CallbackSwitch implements Route
{
    use RouteSelectMethods;

    private $routes;
    private $callback;

    /**
     * @param Route[]  $routes   associative array with route name keys
     * @param callable $callback function (ServerRequestInterface): string
     */
    public function __construct(array $routes, callable $callback)
    {
        $this->routes   = $routes;
        $this->callback = $callback;
    }

    public function forward(Request $request, Response $prototype): Response
    {
        $name  = ($this->callback)($request);
        $route = $this->routes[$name] ?? null;

        return $route ? $route->forward($request, $prototype) : $prototype;
    }

    public function select(string $path): Route
    {
        return $this->getRoute(...$this->splitPath($path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        throw new Exception\EndpointCallException('Cannot resolve specific Uri for callback switch');
    }
}
