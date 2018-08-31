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


class PathSwitch implements Route
{
    use RouteSelectMethods;
    use Route\Gate\Pattern\PathContextMethods;

    const ROOT_PATH = 'HOME';

    protected $routes = [];
    protected $root;

    /**
     * @param Route[] $routes
     * @param Route   $root
     */
    public function __construct(array $routes, ?Route $root = null)
    {
        $this->routes = $routes;
        $this->root   = $root;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        [$segment, $relativePath] = $this->splitRelativePath($request);

        if (!$segment && $this->root) {
            return $this->root->forward($request, $prototype);
        }

        $route = $this->routes[$segment] ?? null;
        return $route
            ? $route->forward($request->withAttribute(static::PATH_ATTRIBUTE, $relativePath), $prototype)
            : $prototype;
    }

    public function select(string $path): Route
    {
        if ($path === static::ROOT_PATH && $this->root) { return $this->root; }

        [$id, $path] = $this->splitPath($path);
        return new Route\Gate\PathSegmentGate($id, $this->getRoute($id, $path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if (!$this->root) {
            throw new EndpointCallException('Uri not defined in gateway route');
        }

        return $this->root->uri($prototype, $params);
    }
}
