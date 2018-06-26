<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Endpoint;

use Polymorphine\Routing\Exception\SwitchCallException;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Routing\Exception\InvalidUriParamsException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class ResourceEndpoint implements Route
{
    use Route\Pattern\PathContextMethods;

    public const INDEX  = 'INDEX'; //pseudo method
    public const GET    = 'GET';
    public const POST   = 'POST';
    public const PUT    = 'PUT';
    public const PATCH  = 'PATCH';
    public const DELETE = 'DELETE';

    private $path;
    private $handlers;

    /**
     * @param string     $path
     * @param callable[] $handlers
     */
    public function __construct(string $path, array $handlers)
    {
        $this->path     = $path;
        $this->handlers = $handlers;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        if (!$path = $this->matchingPath($request)) { return $prototype; }

        $method = $request->getMethod();
        if ($method === self::GET) {
            return $this->dispatchGetMethod($request, $path) ?? $prototype;
        }

        $id = $this->getIdSegment($path);
        if ($method === self::POST) {
            if ($id) { return $prototype; }
            return $this->handlerResponse(self::POST, $request) ?? $prototype;
        }

        return $this->dispatchItemMethod($method, $request, $id) ?? $prototype;
    }

    public function select(string $path): Route
    {
        throw new SwitchCallException(sprintf('Gateway not found for path `%s`', $path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $id = ($params) ? $params['id'] ?? array_shift($params) : '';

        if ($id && !$this->validId($id)) {
            $message = 'Cannot build valid uri string with `%s` id param for `%s` resource path';
            throw new InvalidUriParamsException(sprintf($message, $id, $this->path));
        }

        $path = ($id) ? $this->path . '/' . $id : $this->path;

        if ($path[0] !== '/') {
            $path = $this->resolveRelativePath($path, $prototype);
        } elseif ($prototype->getPath()) {
            throw new UnreachableEndpointException(sprintf('Path conflict for `%s` resource uri', $path));
        }

        return $prototype->withPath($path);
    }

    protected function validId(string $id)
    {
        return is_numeric($id);
    }

    protected function handlerResponse($name, ServerRequestInterface $request)
    {
        $handler = $this->handlers[$name] ?? null;
        return $handler ? $handler($request) : null;
    }

    private function dispatchItemMethod($name, ServerRequestInterface $request, string $id)
    {
        if (!$id || !$this->validId($id)) { return null; }
        return $this->handlerResponse($name, $request->withAttribute('id', $id));
    }

    private function dispatchGetMethod(ServerRequestInterface $request, string $path)
    {
        return ($path === $this->path)
            ? $this->handlerResponse(self::INDEX, $request)
            : $this->dispatchItemMethod(self::GET, $request, $this->getIdSegment($path));
    }

    private function resolveRelativePath($path, UriInterface $prototype)
    {
        return '/' . ltrim($prototype->getPath() . '/' . $path, '/');
    }

    private function matchingPath(ServerRequestInterface $request): ?string
    {
        $path = ($this->path[0] === '/') ? $request->getUri()->getPath() : $this->relativePath($request);
        return strpos($path, $this->path) === 0 ? $path : null;
    }

    private function getIdSegment(string $path): string
    {
        $remainingSegments = $this->newPathContext($path, $this->path);
        [$id, ] = explode('/', $remainingSegments, 2) + ['', null];
        return $id;
    }
}
