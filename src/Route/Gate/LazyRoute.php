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
use Polymorphine\Routing\Map\Trace;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


/**
 * Gate route that invokes further routing structure with
 * given callback and passes messages further.
 */
class LazyRoute implements Route
{
    private $routeCallback;
    private $route;

    /**
     * @param callable $routeCallback fn() => Route
     */
    public function __construct(callable $routeCallback)
    {
        $this->routeCallback = $routeCallback;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $this->invokedRoute()->forward($request, $prototype);
    }

    public function select(string $path): Route
    {
        return $this->invokedRoute()->select($path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->invokedRoute()->uri($prototype, $params);
    }

    public function routes(Trace $trace): void
    {
        $trace->follow($this->invokedRoute());
    }

    protected function invokedRoute(): Route
    {
        return $this->route ?? $this->route = ($this->routeCallback)();
    }
}
