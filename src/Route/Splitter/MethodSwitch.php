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
use Polymorphine\Routing\Map\Trace;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UriInterface;


/**
 * Aggregated Route dispatching incoming requests based on its http method.
 * Scans all routes for OPTIONS requests to determine allowed methods.
 */
class MethodSwitch implements Route
{
    use RouteSelectMethods;

    private $routes;
    private $implicit;

    /**
     * Implicit method Route is for convenience only - it is assumed when method
     * is not specified in selection path. It will be used when creating URI
     * directly for this switch context or select path will not match any of
     * defined routes.
     *
     * @param Route[] $routes   associative array with http method keys (GET, POST, PATCH... etc.)
     * @param string  $implicit key from provided $routes (ignored if none match)
     */
    public function __construct(array $routes, ?string $implicit = 'GET')
    {
        $this->routes   = $routes;
        $this->implicit = isset($routes[$implicit]) ? $implicit : null;
    }

    public function forward(Request $request, Response $prototype): Response
    {
        $method = $request->getMethod();
        if ($method === 'OPTIONS') {
            return $this->options($request, $prototype);
        }

        $route = $this->methodRoute($method);
        return $route ? $route->forward($request, $prototype) : $prototype;
    }

    public function select(string $path): Route
    {
        [$id, $nextPath] = $this->splitPath($path);

        if ($id && !isset($this->routes[$id]) && $this->implicit) {
            return $this->routes[$this->implicit]->select($path);
        }
        return $this->getRoute($id, $nextPath);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if ($this->implicit) {
            return $this->routes[$this->implicit]->uri($prototype, $params);
        }
        throw Route\Exception\AmbiguousEndpointException::forSwitchContext();
    }

    public function routes(Trace $trace): void
    {
        if ($this->implicit) {
            $trace->withMethod($this->implicit)
                  ->withExcludedHops(array_keys($this->routes))
                  ->follow($this->routes[$this->implicit]);
        }

        foreach ($this->routes as $name => $route) {
            $trace->nextHop($name)
                  ->withMethod($name)
                  ->follow($route);
        }
    }

    private function options(Request $request, Response $prototype): Response
    {
        if ($route = $this->methodRoute('OPTIONS')) {
            return $route->forward($request->withoutAttribute(self::METHODS_ATTRIBUTE), $prototype);
        }

        $methods = array_filter(
            $request->getAttribute(self::METHODS_ATTRIBUTE, []),
            $this->checkEndpointCallback($request, $prototype)
        );
        return $methods ? $prototype->withHeader('Allow', implode(', ', $methods)) : $prototype;
    }

    private function checkEndpointCallback(Request $request, Response $prototype): callable
    {
        return function ($method) use ($request, $prototype) {
            if (!isset($this->routes[$method])) { return false; }

            $request = $request->withAttribute(self::METHODS_ATTRIBUTE, [$method]);
            return $this->routes[$method]->forward($request, $prototype) !== $prototype;
        };
    }

    private function methodRoute(string $method): ?Route
    {
        return $this->routes[$method] ?? null;
    }
}
