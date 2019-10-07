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
use Polymorphine\Routing\Map\Trace;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class MockedRoute implements Route
{
    /** @var ServerRequestInterface */
    public $forwardedRequest;
    public $response;
    public $uri;
    public $prototype;
    public $params;
    public $path;
    public $trace;
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
        $this->prototype = $prototype;
        $this->params    = $params;
        return $this->uri ?: $prototype;
    }

    public function routes(Trace $trace): void
    {
        $this->trace = $trace;
        $trace->endpoint();
    }
}
