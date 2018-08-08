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


class ResourceGateway implements Route
{
    private $id;
    private $resource;

    public function __construct(string $id, Route $resource)
    {
        $this->id       = $id;
        $this->resource = $resource;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $this->resource->forward($request, $prototype);
    }

    public function select(string $path): Route
    {
        return $this->resource->select('GET' . Route::PATH_SEPARATOR . $path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return isset($params[$this->id])
            ? $this->resource->select('GET')->uri($prototype, $params)
            : $this->resource->select('GET.index')->uri($prototype, $params);
    }
}
