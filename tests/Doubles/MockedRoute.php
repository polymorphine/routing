<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Doubles;

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class MockedRoute implements Route
{
    /** @var ServerRequestInterface */
    public $forwardedRequest;
    public $response;
    public $uri;
    public $path;
    public $mappedPath;
    public $subRoute;

    public function __construct(?ResponseInterface $response = null, ?UriInterface $uri = null)
    {
        $this->response = $response;
        $this->uri      = $uri;
    }

    public static function response(string $response)
    {
        return new self(new FakeResponse($response), null);
    }

    public static function withUri(string $uri)
    {
        return new self(null, FakeUri::fromString($uri));
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $this->forwardedRequest = $request;
        return $this->response ?? $prototype;
    }

    public function select(string $path): Route
    {
        $this->path     = $path;
        $this->subRoute = clone $this;
        unset($this->subRoute->path);
        return $this->subRoute;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if (!$this->uri) { return $prototype; }

        $part = $this->uri->getScheme() and $prototype = $prototype->withScheme($part);
        $part = $this->uri->getHost() and $prototype = $prototype->withHost($part);
        $part = $this->uri->getPath() and $prototype = $prototype->withPath($part);
        $part = $this->uri->getQuery() and $prototype = $prototype->withQuery($part);
        $part = $this->uri->getPort() and $prototype = $prototype->withPort($part);

        return $this->uri = $prototype;
    }

    public function routes(string $path, UriInterface $uri): array
    {
        $path = ltrim($path . '.end', '.');
        return $this->mappedPath = [$path => (string) $uri];
    }
}
