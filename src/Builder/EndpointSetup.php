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
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;


class EndpointSetup
{
    use GateBuildMethods;
    use EndpointBuilderMethods;

    private $container;
    private $routerCallback;

    public function __construct(?ContainerInterface $container = null, ?callable $routerCallback = null)
    {
        $this->container      = $container;
        $this->routerCallback = $routerCallback;
    }

    public function callback(callable $callback): Route
    {
        return $this->wrapCallbackRoute($callback);
    }

    public function handler(RequestHandlerInterface $handler): Route
    {
        return $this->wrapHandlerRoute($handler);
    }

    public function join(Route $route): Route
    {
        return $this->wrapJoinedRoute($route);
    }

    public function lazy(callable $routeCallback): Route
    {
        return $this->wrapLazyRoute($routeCallback);
    }

    public function redirect(string $path, int $code = 301): Route
    {
        return $this->wrapRedirectRoute($path, $code);
    }

    public function factory(string $className): Route
    {
        return $this->wrapFactoryRoute($className);
    }
}
