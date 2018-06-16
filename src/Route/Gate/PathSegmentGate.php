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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class PathSegmentGate implements Route
{
    private $segment;
    private $route;

    public function __construct(string $segment, Route $route)
    {
        $this->segment = $segment;
        $this->route   = $route;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $relativePath = $request->getAttribute(static::PATH_ATTRIBUTE) ?? $this->splitUriPath($request->getUri());
        return $this->segment === array_shift($relativePath)
            ? $this->route->forward($request->withAttribute(static::PATH_ATTRIBUTE, $relativePath), $prototype)
            : $prototype;
    }

    public function select(string $path): Route
    {
        return new self($this->segment, $this->route->select($path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $path = $prototype->getPath() . '/' . $this->segment;
        return $this->route->uri($prototype->withPath($path), $params);
    }

    private function splitUriPath(UriInterface $uri): array
    {
        $path = ltrim($uri->getPath(), '/');
        return explode('/', $path);
    }
}
