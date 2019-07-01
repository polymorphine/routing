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


/**
 * Aggregated Route where selection of concrete Route from its Route
 * collection is based on relative (to routing root) URI path.
 */
class PathSwitch implements Route
{
    use RouteSelectMethods;
    use Route\Gate\Pattern\PathContextMethods;

    public const ROOT_PATH = 'ROOT';

    private $routes = [];
    private $root;
    private $rootLabel;

    /**
     * Root Route represents fully traversed path in routing structure,
     * and can be selected explicitly with (provided od default) $rootLabel.
     * Only when root route is defined this aggregate instance can produce
     * its own URI, because it assumes that no further path will be required.
     * Root route defined with path constraints will detect conflict at its
     * URI build and UnreachableEndpointException will be thrown.
     *
     * @param Route[] $routes
     * @param Route   $root
     * @param string  $rootLabel label used to select root path route (if defined)
     */
    public function __construct(array $routes, ?Route $root = null, string $rootLabel = self::ROOT_PATH)
    {
        $this->routes    = $routes;
        $this->root      = $root;
        $this->rootLabel = $rootLabel;
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
        if ($path === $this->rootLabel && $this->root) {
            return new Route\Gate\PathEndGate($this->root);
        }

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
