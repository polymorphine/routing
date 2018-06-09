<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Doubles;

use Polymorphine\Routing\Route\Endpoint;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class DummyEndpoint extends Endpoint
{
    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $prototype->withStatus(200);
    }
}
