<?php declare(strict_types=1);

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
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * Endpoint passing request to given RequestHandlerInterface.
 */
class HandlerEndpoint extends Endpoint
{
    private RequestHandlerInterface $handler;

    /**
     * @param RequestHandlerInterface $handler
     */
    public function __construct(RequestHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    protected function execute(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $this->handler->handle($request);
    }
}
