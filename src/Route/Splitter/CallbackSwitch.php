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
 * Aggregated Route dispatching incoming requests based on return string
 * from provided callback.
 */
class CallbackSwitch implements Route
{
    use RouteSelectMethods;

    private $routes;
    private $idCallback;
    private $implicit;

    /**
     * Implicit Route name is for convenience only - it is assumed when route
     * is not specified in selection path. It will be used when creating URI
     * directly for this switch context or select path will not match any of
     * defined routes.
     *
     * @param Route[]     $routes     associative array with route name keys
     * @param callable    $idCallback fn(ServerRequestInterface) => string
     * @param string|null $implicit   key from provided $routes (ignored if none match)
     */
    public function __construct(array $routes, callable $idCallback, ?string $implicit = null)
    {
        $this->routes     = $routes;
        $this->idCallback = $idCallback;
        $this->implicit   = isset($routes[$implicit]) ? $implicit : null;
    }

    public function forward(Request $request, Response $prototype): Response
    {
        $id    = ($this->idCallback)($request);
        $route = $this->routes[$id] ?? null;

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
            $trace->withExcludedHops(array_keys($this->routes))
                  ->follow($this->routes[$this->implicit]);
        }

        foreach ($this->routes as $name => $route) {
            $trace->nextHop($name)
                  ->follow($route);
        }
    }
}
