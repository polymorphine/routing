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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class ContainerMiddleware implements MiddlewareInterface
{
    private $container;
    private $middlewareId;

    public function __construct(ContainerInterface $container, string $middlewareId)
    {
        $this->container    = $container;
        $this->middlewareId = $middlewareId;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middleware()->process($request, $handler);
    }

    private function middleware(): MiddlewareInterface
    {
        return $this->container->get($this->middlewareId);
    }
}
