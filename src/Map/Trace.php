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
    private $routingPath;
    private $uriTemplate;
    private $methods;

    private $excludedLabels = [];
    private $lockedUriPath  = false;
    private $rootLabel;

    public function __construct(Map $map, UriInterface $uriTemplate, string $rootLabel = 'ROOT')
    {
        $this->map         = $map;
        $this->uriTemplate = $uriTemplate;
        $this->rootLabel   = $rootLabel;
    }

    public function endpoint(): void
    {
        $path = $this->routingPath ?? $this->accessibleRootLabel();
        $uri  = rawurldecode((string) $this->uriTemplate);
        foreach ($this->methods ?? ['*'] as $method) {
            $this->map->addPath(new Path($path, $method, $uri));
        }
    }

    public function follow(Route $route): void
    {
        $route->routes($this);
    }

    public function nextHop(string $label): self
    {
        $clone = clone $this;
        $clone->routingPath    = $this->expandPath($label);
        $clone->excludedLabels = [];
        return $clone;
    }

    public function withMethod(string ...$methods): self
    {
        $clone = clone $this;
        $clone->methods = isset($this->methods) ? array_intersect($this->methods, $methods) : $methods;
        return $clone;
    }

    public function withPattern(Route\Gate\Pattern $pattern): self
    {
        $clone = clone $this;
        $clone->uriTemplate = $this->buildUriTemplate($pattern);
        return $clone;
    }

    public function withExcludedHops(array $labels): self
    {
        $clone = clone $this;
        $clone->excludedLabels = array_merge($this->excludedLabels, $labels);
        return $clone;
    }

    public function withLockedUriPath(): self
    {
        $clone = clone $this;
        $clone->lockedUriPath = true;
        return $clone;
    }

    private function buildUriTemplate(Route\Gate\Pattern $pattern): UriInterface
    {
        try {
            $template = $pattern->templateUri($this->uriTemplate);
        } catch (Exception\InvalidUriPrototypeException $e) {
            throw new Exception\UnreachableEndpointException($e->getMessage());
        }

        if ($this->lockedUriPath && $template->getPath() !== $this->uriTemplate->getPath()) {
            $message = 'Cannot append path segment to root PathSwitch context on route `%s`';
            throw new Exception\UnreachableEndpointException(sprintf($message, $this->routingPath));
        }

        return $template;
    }

    private function accessibleRootLabel(): string
    {
        if ($this->isExcluded($this->rootLabel)) {
            $message = 'Unselectable root route `%s` (check route name conflict on first splitter)';
            throw new Exception\UnreachableEndpointException(sprintf($message, $this->rootLabel));
        }

        return $this->rootLabel;
    }

    private function expandPath(string $label): string
    {
        if ($this->isExcluded($label)) {
            $message = 'Unselectable route `%s` on implicit path of `%s` splitter';
            $path    = $this->routingPath ?? $this->rootLabel;
            throw new Exception\UnreachableEndpointException(sprintf($message, $label, $path));
        }

        return isset($this->routingPath) ? $this->routingPath . Route::PATH_SEPARATOR . $label : $label;
    }

    private function isExcluded(string $label): bool
    {
        return $this->excludedLabels && in_array($label, $this->excludedLabels, true);
    }
}
