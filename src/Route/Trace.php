<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route;

use Polymorphine\Routing\Map;
use Polymorphine\Routing\Route;
use Psr\Http\Message\UriInterface;


class Trace
{
    private $map;
    private $uri;
    private $methods;
    private $path;

    public function __construct(Map $map, UriInterface $uri)
    {
        $this->map = $map;
        $this->uri = $uri;
    }

    public function endpoint(): void
    {
        foreach ($this->methods ?? ['*'] as $method) {
            $this->map->addEndpoint($this->path ?: '0', $this->uri, $method);
        }
    }

    public function follow(Route $route): void
    {
        $route->routes($this);
    }

    public function nextHop(string $name): self
    {
        $clone = clone $this;
        $clone->path = isset($this->path) ? $this->path . Route::PATH_SEPARATOR . $name : $name;
        return $clone;
    }

    public function withPattern(Route\Gate\Pattern $pattern): self
    {
        $clone = clone $this;
        $clone->uri = $pattern->templateUri($this->uri);
        return $clone;
    }

    public function withMethod(string ...$methods): self
    {
        $methods = isset($this->methods)
            ? array_intersect($this->methods, $methods)
            : $methods;

        $clone = clone $this;
        $clone->methods = $methods;
        return $clone;
    }
}
