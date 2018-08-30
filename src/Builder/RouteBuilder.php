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

use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Route;
use Psr\Http\Server\RequestHandlerInterface;


class RouteBuilder implements Builder
{
    use GateBuildMethods;

    private $context;

    public function __construct(BuilderContext $context)
    {
        $this->context = $context;
    }

    public function build(): Route
    {
        return $this->context->build();
    }

    public function callback(callable $callback): void
    {
        $this->context->setCallbackRoute($callback);
    }

    public function handler(RequestHandlerInterface $handler): void
    {
        $this->context->setHandlerRoute($handler);
    }

    public function lazy(callable $routeCallback): void
    {
        $this->context->setLazyRoute($routeCallback);
    }

    public function redirect(string $routingPath, int $code = 301): void
    {
        $this->context->setRedirectRoute($routingPath, $code);
    }

    public function factory(string $className): void
    {
        $this->context->setFactoryRoute($className);
    }

    public function join(Route $route): void
    {
        $this->context->setRoute($route);
    }

    public function joinBuilder(?Route &$route): void
    {
        $this->context->setBuilder(new LinkedRouteBuilder($route));
    }

    public function pathSwitch(array $routes = []): PathSegmentSwitchBuilder
    {
        return $this->contextBuilder(new PathSegmentSwitchBuilder($this->context, $routes));
    }

    public function responseScan(array $routes = []): ResponseScanSwitchBuilder
    {
        return $this->contextBuilder(new ResponseScanSwitchBuilder($this->context, $routes));
    }

    public function methodSwitch(array $routes = []): MethodSwitchBuilder
    {
        return $this->contextBuilder(new MethodSwitchBuilder($this->context, $routes));
    }

    public function resource(array $routes = []): ResourceSwitchBuilder
    {
        return $this->contextBuilder(new ResourceSwitchBuilder($this->context, $routes));
    }

    private function contextBuilder($builder)
    {
        $this->context->setBuilder($builder);
        return $builder;
    }
}
