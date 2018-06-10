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


class ConditionGate implements Route
{
    private $condition;
    private $route;

    public function __construct(Closure $condition, Route $route)
    {
        $this->condition = $condition;
        $this->route     = $route;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $this->condition->__invoke($request)
            ? $this->route->forward($request, $prototype)
            : $prototype;
    }

    public function route(string $path): Route
    {
        return $this->route->route($path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->route->uri($prototype, $params);
    }
}
