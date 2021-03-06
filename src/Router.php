<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;


class Router implements RequestHandlerInterface
{
    private Route             $route;
    private UriInterface      $baseUri;
    private ResponseInterface $baseResponse;
    private string            $rootPath;

    /**
     * Response prototype is used for responses handled within routing tree
     * without delegating request to endpoint handler. This allows routing
     * to avoid dependency on concrete ResponseInterface implementation,
     * utilise Null Object Pattern and communicating internally.
     *
     * For example:
     * - same instance may indicate unprocessed response (NullResponse)
     * - conditional redirect might be returned based on middleware context
     * - resource methods might be inspected within routing (OPTIONS method)
     *
     * @param Route             $route
     * @param UriInterface      $baseUri      prototype on which endpoint uri will be built
     * @param ResponseInterface $baseResponse prototype used as internal router response
     * @param string            $rootPath     routing path selecting root route
     */
    public function __construct(
        Route $route,
        UriInterface $baseUri,
        ResponseInterface $baseResponse,
        string $rootPath = 'ROOT'
    ) {
        $this->route        = $route;
        $this->baseUri      = $baseUri;
        $this->baseResponse = $baseResponse;
        $this->rootPath     = $rootPath;
    }

    public static function withPrototypeFactories(
        Route $route,
        UriFactoryInterface $uriFactory,
        ResponseFactoryInterface $responseFactory,
        string $rootPath = 'ROOT'
    ): self {
        return new self($route, $uriFactory->createUri(), $responseFactory->createResponse(404), $rootPath);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->route->forward($request, $this->baseResponse);
    }

    /**
     * @param string $path by default dot separated list of switch identifiers
     *
     * @throws Route\Exception\RouteNotFoundException
     *
     * @return Router with changed root context
     */
    public function select(string $path): self
    {
        return $path !== $this->rootPath
            ? new static($this->route->select($path), $this->baseUri, $this->baseResponse)
            : $this;
    }

    /**
     * @param string $path   defined in select() method
     * @param array  $params named or ordered variables for dynamic uri patterns
     *
     * @throws Route\Exception\RouteNotFoundException
     * @throws Route\Exception\UriBuildException
     *
     * @return UriInterface
     */
    public function uri(string $path, array $params = []): UriInterface
    {
        try {
            return $path !== $this->rootPath
                ? $this->route->select($path)->uri($this->baseUri, $params)
                : $this->route->uri($this->baseUri, $params);
        } catch (Route\Exception\RouteNotFoundException $e) {
            throw $e->withPathInfo($path);
        } catch (Route\Exception\UriBuildException $e) {
            throw $e->withPathInfo($path);
        }
    }

    /**
     * Builds routing map with path names and corresponding request
     * methods and URI templates.
     *
     * @throws Map\Exception\UnreachableEndpointException
     *
     * @return Map\Path[]
     */
    public function routes(): array
    {
        $map   = new Map();
        $trace = new Map\Trace($map, $this->baseUri, $this->rootPath);

        $trace->follow($this->route);

        return $map->paths();
    }
}
