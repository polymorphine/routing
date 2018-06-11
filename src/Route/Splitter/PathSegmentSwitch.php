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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class PathSegmentSwitch extends Route\Splitter
{
    public const PATH_ATTR = 'route.path';

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $relativePath = $request->getAttribute(static::PATH_ATTR) ?? $this->splitUriPath($request->getUri());
        $segment = array_shift($relativePath);

        $route = $this->routes[$segment] ?? null;
        if (!$route) { return $prototype; }

        return $route->forward($request->withAttribute(static::PATH_ATTR, $relativePath), $prototype);
    }

    public function route(string $path): Route
    {
        return parent::route($path);
    }

    private function splitUriPath(UriInterface $uri): array
    {
        $path = ltrim($uri->getPath(), '/');
        return explode('/', $path);
    }
}
