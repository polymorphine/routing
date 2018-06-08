<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class Router
{
    private $route;
    private $uriPrototype;
    private $notFound;

    public function __construct(Route $route, UriInterface $uriPrototype, ResponseInterface $notFound)
    {
        $this->route        = $route;
        $this->uriPrototype = $uriPrototype;
        $this->notFound     = $notFound;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->route->forward($request, $this->notFound);
    }

    public function uri(string $path, array $params = [])
    {
        return $this->route->gateway($path)->uri($this->uriPrototype, $params);
    }

    public function route(string $path)
    {
        return new static($this->route->gateway($path), $this->uriPrototype, $this->notFound);
    }
}
