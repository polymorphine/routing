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
 * Gate resolving URI based on existence of concrete parameter.
 * Used primarily for convenience in REST resource paths, where
 * route path is assumed, and given parameter will decide to
 * return URI for resource "list" or concrete resource.
 */
class UriAttributeSelect implements Route
{
    private $id;
    private $itemPath;
    private $indexPath;
    private $resource;

    /**
     * @param Route  $resource  composite route with both $itemPath and $indexPath routes
     * @param string $id        name of URI parameter that indicates item path selection
     * @param string $itemPath  path to select URI from when $id is present
     * @param string $indexPath path to select URI from when $id is missing
     */
    public function __construct(Route $resource, string $id, string $itemPath, string $indexPath)
    {
        $this->resource  = $resource;
        $this->id        = $id;
        $this->itemPath  = $itemPath;
        $this->indexPath = $indexPath;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $this->resource->forward($request, $prototype);
    }

    public function select(string $path): Route
    {
        return $this->resource->select($path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return isset($params[$this->id])
            ? $this->resource->select($this->itemPath)->uri($prototype, $params)
            : $this->resource->select($this->indexPath)->uri($prototype, $params);
    }

    public function routes(string $path, UriInterface $uri): array
    {
        return $this->resource->routes($path, $uri);
    }
}
