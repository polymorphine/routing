<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class RouteHandler implements RequestHandlerInterface
{
    private $route;
    private $notFound;

    public function __construct(Route $route, ResponseInterface $notFound)
    {
        $this->route    = $route;
        $this->notFound = $notFound;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->route->forward($request, $this->notFound);
    }
}
