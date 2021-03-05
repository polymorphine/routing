<?php declare(strict_types=1);

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
use Polymorphine\Routing\Route\Gate;
use Polymorphine\Routing\Map\Trace;
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
    use Gate\Pattern\PathContextMethods;

    private array  $routes;
    private ?Route $root;

    /**
     * Root Route represents fully traversed path in routing structure.
     * Only when root route is defined this aggregate instance can produce
     * its own URI, because it assumes that no further path will be required.
     *
     * @param Route[]    $routes
     * @param Route|null $root
     */
    public function __construct(array $routes, ?Route $root = null)
    {
        $this->routes = $routes;
        $this->root   = $root;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $segment = $this->pathSegment($request);

        if (!$segment && $this->root) {
            return $this->root->forward($request, $prototype);
        }

        $route = $this->routes[$segment] ?? null;
        return $route
            ? $route->forward($this->newContextRequest($request), $prototype)
            : $prototype;
    }

    public function select(string $path): Route
    {
        [$id, $path] = $this->splitPath($path);
        $pattern = new Gate\Pattern\UriPart\PathSegment($id);
        return new Gate\PatternGate($pattern, $this->getRoute($id, $path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if (!$this->root) {
            throw Route\Exception\AmbiguousEndpointException::forSwitchContext();
        }

        return $this->root->uri($prototype, $params);
    }

    public function routes(Trace $trace): void
    {
        if ($this->root) {
            $trace->withLockedUriPath()
                  ->withExcludedHops(array_keys($this->routes))
                  ->follow($this->root);
        }
        foreach ($this->routes as $name => $route) {
            $trace->nextHop($name)
                  ->withPattern(new Gate\Pattern\UriPart\PathSegment($name))
                  ->follow($route);
        }
    }
}
