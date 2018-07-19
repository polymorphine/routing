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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Closure;


/**
 * Route that forwards passed request in context of given Closure.
 */
class CallbackGateway implements Route
{
    private $callback;
    private $route;

    /**
     * $callback callable takes ServerRequestInterface parameter and returns same
     * type back if request should be forwarded to given Route or null if request
     * should be blocked and $prototype response returned.
     *
     * NOTE: If request uri is verified it will not be resembled by Uri built
     * with this gate self::uri() method - use Pattern gate instead.
     *
     * @param callable $callback
     * @param Route    $route
     */
    public function __construct(callable $callback, Route $route)
    {
        $this->callback = $callback;
        $this->route    = $route;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $request = ($this->callback)($request);
        return $request ? $this->route->forward($request, $prototype) : $prototype;
    }

    public function select(string $path): Route
    {
        return $this->route->select($path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->route->uri($prototype, $params);
    }
}
