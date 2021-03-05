<?php declare(strict_types=1);

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

    /**
     * @param Map          $map
     * @param UriInterface $uriTemplate
     * @param string       $rootLabel
     */
    public function __construct(Map $map, UriInterface $uriTemplate, string $rootLabel = 'ROOT')
    {
        $this->map         = $map;
        $this->uriTemplate = $uriTemplate;
        $this->rootLabel   = $rootLabel;
    }

    /**
     * Adds fully traced path to routing Map.
     */
    public function endpoint(): void
    {
        $uri = rawurldecode((string) $this->uriTemplate);
        foreach ($this->methods ?? ['*'] as $method) {
            $this->map->addPath(new Path($this->routingPathString(), $method, $uri));
        }
    }

    /**
     * Continues to follow concrete Route path.
     *
     * @param Route $route
     */
    public function follow(Route $route): void
    {
        $route->routes($this);
    }

    /**
     * @param string $label
     *
     * @return static New instance with routing path expanded with new label
     */
    public function nextHop(string $label): self
    {
        $clone = clone $this;
        $clone->routingPath    = $this->expandPath($label);
        $clone->excludedLabels = [];
        return $clone;
    }

    /**
     * @param string ...$methods
     *
     * @return static New instance with filtered http Methods
     */
    public function withMethod(string ...$methods): self
    {
        $clone = clone $this;
        $clone->methods = isset($this->methods) ? array_intersect($this->methods, $methods) : $methods;
        return $clone;
    }

    /**
     * @param Route\Gate\Pattern $pattern
     *
     * @return static New instance with expanded URI pattern
     */
    public function withPattern(Route\Gate\Pattern $pattern): self
    {
        $clone = clone $this;
        $clone->uriTemplate = $this->buildUriTemplate($pattern);
        return $clone;
    }

    /**
     * @param array $labels
     *
     * @return static New instance with labels that Route cannot reach
     */
    public function withExcludedHops(array $labels): self
    {
        $clone = clone $this;
        $clone->excludedLabels = array_merge($this->excludedLabels, $labels);
        return $clone;
    }

    /**
     * @return static New instance with finished path constraint
     */
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
        } catch (Route\Exception\InvalidUriPrototypeException $e) {
            throw Map\Exception\UnreachableEndpointException::uriConflict($e, $this->routingPathString());
        }

        if ($this->lockedUriPath && $template->getPath() !== $this->uriTemplate->getPath()) {
            throw Map\Exception\UnreachableEndpointException::unexpectedPathSegment($this->routingPathString());
        }

        return $template;
    }

    private function expandPath(string $label): string
    {
        if ($this->isExcluded($label)) {
            throw Map\Exception\UnreachableEndpointException::labelConflict($label, $this->routingPathString());
        }

        return isset($this->routingPath) ? $this->routingPath . Route::PATH_SEPARATOR . $label : $label;
    }

    private function routingPathString()
    {
        return $this->routingPath ?? $this->accessibleRootLabel();
    }

    private function accessibleRootLabel(): string
    {
        if ($this->isExcluded($this->rootLabel)) {
            throw Map\Exception\UnreachableEndpointException::rootLabelConflict($this->rootLabel);
        }

        return $this->rootLabel;
    }

    private function isExcluded(string $label): bool
    {
        return $this->excludedLabels && in_array($label, $this->excludedLabels, true);
    }
}
