<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Map;

use Polymorphine\Routing\Map;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Psr\Http\Message\UriInterface;


class Trace
{
    private $map;
    private $uri;
    private $methods;
    private $path;
    private $excluded = [];
    private $rootLabel;

    public function __construct(Map $map, UriInterface $uri, string $rootLabel = 'ROOT')
    {
        $this->map       = $map;
        $this->uri       = $uri;
        $this->rootLabel = $rootLabel;
    }

    public function endpoint(): void
    {
        $path = isset($this->path) ? $this->path : $this->rootLabel;
        $uri  = rawurldecode((string) $this->uri);
        foreach ($this->methods ?? ['*'] as $method) {
            $this->map->addPath(new Path($path, $method, $uri));
        }
    }

    public function follow(Route $route): void
    {
        $route->routes($this);
    }

    public function nextHop(string $name): self
    {
        if ($this->excluded && in_array($name, $this->excluded, true)) {
            $message = 'Unselectable route `%s` on implicit path of `%s` splitter';
            $path    = $this->path ?? $this->rootLabel;
            throw new Exception\UnreachableEndpointException(sprintf($message, $name, $path));
        }

        $clone = clone $this;
        $clone->path     = isset($this->path) ? $this->path . Route::PATH_SEPARATOR . $name : $name;
        $clone->excluded = [];
        return $clone;
    }

    public function withExcludedHops(array $exclude): self
    {
        $clone = clone $this;
        $clone->excluded = $this->excluded ? array_merge($this->excluded, $exclude) : $exclude;
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
