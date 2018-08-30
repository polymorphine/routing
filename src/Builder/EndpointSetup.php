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
use Psr\Http\Server\RequestHandlerInterface;


class EndpointSetup
{
    use GateBuildMethods;

    private $context;

    public function __construct(BuilderContext $context)
    {
        $this->context = $context;
    }

    public function callback(callable $callback): Route
    {
        $this->context->setCallbackRoute($callback);
        return $this->context->build();
    }

    public function handler(RequestHandlerInterface $handler): Route
    {
        $this->context->setHandlerRoute($handler);
        return $this->context->build();
    }

    public function lazy(callable $routeCallback): Route
    {
        $this->context->setLazyRoute($routeCallback);
        return $this->context->build();
    }

    public function redirect(string $routingPath, int $code = 301): Route
    {
        $this->context->setRedirectRoute($routingPath, $code);
        return $this->context->build();
    }

    public function factory(string $className): Route
    {
        $this->context->setFactoryRoute($className);
        return $this->context->build();
    }

    public function join(Route $route)
    {
        $this->context->setRoute($route);
        return $this->context->build();
    }
}
