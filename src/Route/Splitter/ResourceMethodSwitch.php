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
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Routing\Exception\InvalidUriParamsException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class ResourceMethodSwitch implements Route
{
    use RouteSelectMethods;
    use Route\Gate\Pattern\PathContextMethods;

    public const INDEX  = 'INDEX'; //pseudo method
    public const GET    = 'GET';
    public const POST   = 'POST';
    public const PUT    = 'PUT';
    public const PATCH  = 'PATCH';
    public const DELETE = 'DELETE';

    private $path;
    private $routes;

    /**
     * @param string  $path
     * @param Route[] $routes
     */
    public function __construct(string $path, array $routes)
    {
        $this->path   = $path;
        $this->routes = $routes;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $path = $this->matchingPath($request);
        if (!$path) { return $prototype; }

        $method = $request->getMethod();
        $route  = $this->getMethodRoute($method, $path);
        if (!$route) { return $prototype; }

        $request = $this->setAttributes($request, $path);
        if (!$request) { return $prototype; }

        return $route->forward($request, $prototype);
    }

    public function select(string $path): Route
    {
        [$id, $path] = $this->splitPath($path);
        return new self($this->path, [$id => $this->getRoute($id, $path)]);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $id = ($params) ? $params['id'] ?? array_shift($params) : '';

        if ($id && !$this->validId($id)) {
            $message = 'Cannot build valid uri string with `%s` id param for `%s` resource path';
            throw new InvalidUriParamsException(sprintf($message, $id, $this->path));
        }

        $path = ($id) ? $this->path . '/' . $id : $this->path;
        $uri  = $prototype->withPath($this->resolvePathType($path, $prototype));

        $method = $this->getUriMethod($path);
        if (!$route = $this->getMethodRoute($method, $path)) {
            $message = 'Default `%s` method not defined to resolve Uri for multiple methods route';
            throw new EndpointCallException(sprintf($message, $method));
        }
        return $route->uri($uri, $params);
    }

    protected function getMethodRoute(string $method, string $path): ?Route
    {
        if ($path !== $this->path && $method === self::POST) { return null; }
        if ($path === $this->path && $method === self::GET) {
            return $this->routes[self::INDEX] ?? null;
        }
        if ($path === $this->path && $method !== self::INDEX && $method !== self::POST) {
            return null;
        }

        return $this->routes[$method] ?? null;
    }

    protected function setAttributes(ServerRequestInterface $request, string $path): ?ServerRequestInterface
    {
        if ($path === $this->path) {
            return $request->withAttribute(Route::PATH_ATTRIBUTE, '');
        }

        [$id, $path] = $this->splitPathSegment($this->newPathContext($path, $this->path));
        if (!$this->validId($id)) { return null; }

        return $request->withAttribute('id', $id)->withAttribute(Route::PATH_ATTRIBUTE, $path);
    }

    protected function validId(string $id)
    {
        return is_numeric($id);
    }

    private function resolvePathType($path, UriInterface $prototype)
    {
        if ($path[0] === '/' && $prototype->getPath()) {
            throw new UnreachableEndpointException(sprintf('Path conflict for `%s` resource uri', $path));
        }

        return $path[0] === '/' ? $path : '/' . ltrim($prototype->getPath() . '/' . $path, '/');
    }

    private function matchingPath(ServerRequestInterface $request): ?string
    {
        $path = ($this->path[0] === '/') ? $request->getUri()->getPath() : $this->relativePath($request);
        return strpos($path, $this->path) === 0 ? $path : null;
    }

    private function getUriMethod(string $path): string
    {
        if (count($this->routes) !== 1) {
            return ($path === $this->path) ? self::INDEX : self::GET;
        }

        reset($this->routes);
        return key($this->routes);
    }
}
