<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Node;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\Pattern;
use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Builder\Exception;
use Psr\Http\Server\MiddlewareInterface;


trait GateBuildMethods
{
    private Context $context;

    /**
     * Generic gate wrapper that creates gate Route with given callback
     * that will wrap Route passed as its argument.
     *
     * @param callable $routeWrapper fn(Route) => Route
     *
     * @return GateBuildMethods
     */
    public function wrapRouteCallback(callable $routeWrapper): self
    {
        $this->context->addGate($routeWrapper);
        return $this;
    }

    /**
     * Creates MethodGate and (optionally) PatternGate wrapper for built route.
     *
     * @param string       $methods single http method or pipe separated method set like 'GET|POST|DELETE'
     * @param Pattern|null $pattern
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
            $pattern = Pattern\UriPattern::path($path, $regexp);
            return new Route\Gate\PatternGate($pattern, $route);
        });
        return $this;
    }

    /**
     * Creates CallbackGateway wrapper for built route.
     *
     * @param callable $callback fn(ServerRequestInterface) => ?ServerRequestInterface
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

    /**
     * Adds gate Route resolved from passed identifier.
     *
     * NOTE: In order to use this method gate callback in
     * MappedRoutes has to be defined or ConfigException
     * will be thrown.
     *
     * @see \Polymorphine\Routing\Builder\MappedRoutes::gateway()
     *
     * @param string $id
     *
     * @throws Exception\ConfigException
     *
     * @return static
     */
    public function gate(string $id): self
    {
        $this->context->mapGate($id);
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

    /**
     * @param Pattern|null $pattern
     *
     * @return static
     */
    public function get(Pattern $pattern = null): self
    {
        return $this->method('GET', $pattern);
    }

    /**
     * @param Pattern|null $pattern
     *
     * @return static
     */
    public function post(Pattern $pattern = null): self
    {
        return $this->method('POST', $pattern);
    }

    /**
     * @param Pattern|null $pattern
     *
     * @return static
     */
    public function put(Pattern $pattern = null): self
    {
        return $this->method('PUT', $pattern);
    }

    /**
     * @param Pattern|null $pattern
     *
     * @return static
     */
    public function patch(Pattern $pattern = null): self
    {
        return $this->method('PATCH', $pattern);
    }

    /**
     * @param Pattern|null $pattern
     *
     * @return static
     */
    public function delete(Pattern $pattern = null): self
    {
        return $this->method('DELETE', $pattern);
    }

    /**
     * @param Pattern|null $pattern
     *
     * @return static
     */
    public function head(Pattern $pattern = null): self
    {
        return $this->method('HEAD', $pattern);
    }

    /**
     * @param Pattern|null $pattern
     *
     * @return static
     */
    public function options(Pattern $pattern = null): self
    {
        return $this->method('OPTIONS', $pattern);
    }
}
