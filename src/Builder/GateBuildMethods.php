<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\Pattern;
use Psr\Http\Server\MiddlewareInterface;


trait GateBuildMethods
{
    use Pattern\PatternSelection;

    /** @var BuilderContext */
    private $context;

    /**
     * Creates MethodGate and (optionally) PatternGate wrapper for built route.
     *
     * @param string       $methods single http method or pipe separated method set like 'GET|POST|DELETE'
     * @param null|Pattern $pattern
     *
     * @return static
     */
    public function method(string $methods, Pattern $pattern = null): self
    {
        if (isset($pattern)) { $this->pattern($pattern); }
        $this->context->addGate(function (Route $route) use ($methods) {
            return new Route\Gate\MethodGate($methods, $route);
        });
        return $this;
    }

    /**
     * Creates PatternGate wrapper for built route.
     *
     * @param Pattern $pattern
     *
     * @return static
     */
    public function pattern(Pattern $pattern): self
    {
        $this->context->addGate(function (Route $route) use ($pattern) {
            return new Route\Gate\PatternGate($pattern, $route);
        });
        return $this;
    }

    /**
     * Creates PathSegmentGate wrappers for built route.
     *
     * @param string $path
     * @param array  $regexp
     *
     * @return static
     */
    public function path(string $path, array $regexp = []): self
    {
        $this->context->addGate(function (Route $route) use ($path, $regexp) {
            $segments = explode('/', trim($path, '/'));
            while ($segment = array_pop($segments)) {
                $pattern = $this->patternSegment($segment, $regexp);
                $route = $pattern
                    ? new Route\Gate\PatternGate($pattern, $route)
                    : new Route\Gate\PathSegmentGate($segment, $route);
            }
            return $route;
        });
        return $this;
    }

    /**
     * Creates CallbackGateway wrapper for built route.
     *
     * @param callable $callback function(ServerRequestInterface): ?ServerRequestInterface
     *
     * @return static
     */
    public function callbackGate(callable $callback): self
    {
        $this->context->addGate(function (Route $route) use ($callback) {
            return new Route\Gate\CallbackGateway($callback, $route);
        });
        return $this;
    }

    /**
     * Creates MiddlewareGateway wrapper for built route.
     *
     * @param MiddlewareInterface $middleware
     *
     * @return static
     */
    public function middleware(MiddlewareInterface $middleware): self
    {
        $this->context->addGate(function (Route $route) use ($middleware) {
            return new Route\Gate\MiddlewareGateway($middleware, $route);
        });
        return $this;
    }

    public function containerMiddleware(string $middlewareContainerId): self
    {
        $this->context->addContainerMiddlewareGate($middlewareContainerId);
        return $this;
    }

    /**
     * Takes reference variable that can be used to join built routing
     * structure from this point from another routing structure context.
     *
     * @param $routeReference
     *
     * @return static
     */
    public function link(&$routeReference): self
    {
        $this->context->addGate(function (Route $route) use (&$routeReference) {
            $routeReference = $route;
            return $route;
        });
        return $this;
    }

    public function get(Pattern $pattern = null): self
    {
        return $this->method('GET', $pattern);
    }

    public function post(Pattern $pattern = null): self
    {
        return $this->method('POST', $pattern);
    }

    public function put(Pattern $pattern = null): self
    {
        return $this->method('PUT', $pattern);
    }

    public function patch(Pattern $pattern = null): self
    {
        return $this->method('PATCH', $pattern);
    }

    public function delete(Pattern $pattern = null): self
    {
        return $this->method('DELETE', $pattern);
    }

    public function head(Pattern $pattern = null): self
    {
        return $this->method('HEAD', $pattern);
    }

    public function options(Pattern $pattern = null): self
    {
        return $this->method('OPTIONS', $pattern);
    }
}
