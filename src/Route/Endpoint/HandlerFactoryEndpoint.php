<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Endpoint;

use Polymorphine\Routing\Route\Endpoint;
use Polymorphine\Routing\RequestHandlerFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;


class HandlerFactoryEndpoint extends Endpoint
{
    private $factoryCallback;
    private $container;

    /**
     * @param callable           $factoryCallback function(): RequestHandlerFactory
     * @param ContainerInterface $container
     */
    public function __construct(callable $factoryCallback, ContainerInterface $container)
    {
        $this->factoryCallback = $factoryCallback;
        $this->container       = $container;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $this->factory()->createHandler($this->container)->handle($request);
    }

    private function factory(): RequestHandlerFactory
    {
        return ($this->factoryCallback)();
    }
}
