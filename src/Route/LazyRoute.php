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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class LazyRoute implements Route
{
    private $routeCallback;
    private $route;

    public function __construct(callable $routeCallback)
    {
        $this->routeCallback = $routeCallback;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $notFound): ResponseInterface
    {
        return $this->route()->forward($request, $notFound);
    }

    public function gateway(string $path): Route
    {
        return $this->route()->gateway($path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->route()->uri($prototype, $params);
    }

    private function route(): Route
    {
        return $this->route ?? $this->route = ($this->routeCallback)();
    }
}
