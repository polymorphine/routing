<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Polymorphine\Routing\Route;


/**
 * Wrapper transforming Route into RequestHandlerInterface useful in
 * MiddlewareInterface chaining within routing paths.
 */
class RouteHandler implements RequestHandlerInterface
{
    private $route;
    private $prototype;

    /**
     * @param Route             $route
     * @param ResponseInterface $prototype
     */
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
