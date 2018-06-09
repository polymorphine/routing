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
use Closure;


class MockedRoute implements Route
{
    public $id;
    public $callback;
    public $path;

    public function __construct(string $id, Closure $callback = null)
    {
        $this->id       = $id;
        $this->callback = $callback;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        if ($this->callback) {
            return $this->callback->__invoke($request) ?? $prototype;
        }
        return $this->id ? new FakeResponse($this->id) : $prototype;
    }

    public function route(string $path): Route
    {
        $this->path = $path;
        return $this;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->id ? $prototype->withPath($this->id) : $prototype;
    }
}
