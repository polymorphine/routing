<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Routing\Exception\UriParamsException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class ResourceEndpoint implements Route
{
    use LockedGatewayMethod;

    public const INDEX  = 'INDEX';
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

    public function forward(ServerRequestInterface $request, ResponseInterface $notFound): ResponseInterface
    {
        $path = ($this->path[0] !== '/')
            ? $this->relativeRequestPath($request->getUri()->getPath())
            : $this->path;

        if (!$path) { return $notFound; }

        $method = $request->getMethod();

        if ($method === self::GET) {
            return $this->dispatchGetMethod($request, $path) ?? $notFound;
        }

        if ($method === self::POST) {
            return $this->dispatchPostMethod($request, $path) ?? $notFound;
        }

        return $this->dispatchItemMethod($method, $request, $path) ?? $notFound;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $id = ($params) ? $params['id'] ?? array_shift($params) : '';

        if ($id && !$this->validId($id)) {
            $message = 'Cannot build valid uri string with `%s` id param for `%s` resource path';
            throw new UriParamsException(sprintf($message, $id, $this->path));
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

    private function dispatchItemMethod($name, ServerRequestInterface $request, $path)
    {
        $requestPath = $request->getUri()->getPath();
        if (strpos($requestPath, $path) !== 0) { return null; }

        [$id, ] = explode('/', substr($requestPath, strlen($path) + 1), 2) + [false, false];
        if (!$this->validId($id)) { return null; }

        return $this->handlerResponse($name, $request->withAttribute('id', $id));
    }

    private function dispatchPostMethod(ServerRequestInterface $request, $path)
    {
        if ($path !== $request->getUri()->getPath()) { return null; }

        return $this->handlerResponse(self::POST, $request);
    }

    private function dispatchGetMethod(ServerRequestInterface $request, string $path)
    {
        return ($path === $request->getUri()->getPath())
            ? $this->handlerResponse(self::INDEX, $request)
            : $this->dispatchItemMethod(self::GET, $request, $path);
    }

    private function resolveRelativePath($path, UriInterface $prototype)
    {
        if (!$prototypePath = $prototype->getPath()) {
            throw new UnreachableEndpointException('Unresolved relative path');
        }

        return '/' . ltrim($prototypePath . '/' . $path, '/');
    }

    private function relativeRequestPath($path)
    {
        $pos = strpos($path, $this->path);
        if (!$pos || $path[$pos - 1] !== '/') { return null; }

        return substr($path, 0, $pos) . $this->path;
    }
}
