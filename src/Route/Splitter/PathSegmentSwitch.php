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
use Polymorphine\Routing\Exception\EndpointCallException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class PathSegmentSwitch implements Route
{
    use RouteSelectMethods;

    protected $routes = [];

    /**
     * @param Route[] $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $relativePath = $request->getAttribute(static::PATH_ATTRIBUTE) ?? $this->splitUriPath($request->getUri());
        $segment = array_shift($relativePath);

        $route = $this->routes[$segment] ?? null;
        return $route
            ? $route->forward($request->withAttribute(static::PATH_ATTRIBUTE, $relativePath), $prototype)
            : $prototype;
    }

    public function select(string $path): Route
    {
        [$id, $path] = $this->splitPath($path);
        return new Route\Gate\PathSegmentGate($id, $this->getRoute($id, $path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        throw new EndpointCallException('Uri not defined in gateway route');
    }

    private function splitUriPath(UriInterface $uri): array
    {
        $path = ltrim($uri->getPath(), '/');
        return explode('/', $path);
    }
}
