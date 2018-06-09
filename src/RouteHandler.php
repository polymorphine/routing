<?php

/*
 * This file is part of Polymorphine/Routing package.
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
    private $prototype;

    public function __construct(Route $route, ResponseInterface $prototype)
    {
        $this->route     = $route;
        $this->prototype = $prototype;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->route->forward($request, $this->prototype);
    }
}
