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


/**
 * Gate route that allows requests to hit an endpoint even when their
 * path was not entirely matched in preceding routes.
 */
class WildcardPathGate implements Route
{
    private $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $this->route->forward($request->withAttribute(static::WILDCARD_ATTRIBUTE, true), $prototype);
    }

    public function select(string $path): Route
    {
        return new self($this->route->select($path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->route->uri($prototype, $params);
    }
}
