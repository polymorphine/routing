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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class RedirectEndpoint extends Endpoint
{
    private $uriCallback;
    private $statusCode;

    public function __construct(callable $uriCallback, int $statusCode = 301)
    {
        $this->uriCallback = $uriCallback;
        $this->statusCode  = $statusCode;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $prototype->withStatus($this->statusCode)->withHeader('Location', ($this->uriCallback)());
    }
}
