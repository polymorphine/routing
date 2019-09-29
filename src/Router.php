<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing;

use Polymorphine\Routing\Map\Trace;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;


class Router implements RequestHandlerInterface
{
    private $route;
    private $baseUri;
    private $baseResponse;

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
     */
    public function __construct(Route $route, UriInterface $baseUri, ResponseInterface $baseResponse)
    {
        $this->route        = $route;
        $this->baseUri      = $baseUri;
        $this->baseResponse = $baseResponse;
    }

    public static function withPrototypeFactories(
        Route $route,
        UriFactoryInterface $uriFactory,
        ResponseFactoryInterface $responseFactory
    ): self {
        return new self($route, $uriFactory->createUri(), $responseFactory->createResponse(404));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->route->forward($request, $this->baseResponse);
    }

    /**
     * @param string $path as defined in uri() method
     *
     * @return Router with changed root context
     */
    public function select(string $path): Router
    {
        return new static($this->route->select($path), $this->baseUri, $this->baseResponse);
    }

    /**
     * @param string $path   by default dot separated list of switch identifiers
     * @param array  $params named or ordered variables for dynamic uri patterns
     *
     * @return UriInterface
     */
    public function uri(string $path, array $params = []): UriInterface
    {
        return $this->route->select($path)->uri($this->baseUri, $params);
    }

    /**
     * @return array of routing paths associated with its URI templates
     */
    public function routes(): array
    {
        $map   = new Map();
        $trace = new Trace($map, $this->baseUri);

        $trace->follow($this->route);

        return $map->toArray();
    }
}
