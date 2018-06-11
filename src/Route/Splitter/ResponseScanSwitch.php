<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Splitter;

use Polymorphine\Routing\Route\Splitter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class ResponseScanSwitch extends Splitter
{
    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $response = $prototype;
        foreach ($this->routes as $route) {
            $response = $route->forward($request, $prototype);
            if ($response !== $prototype) { break; }
        }

        return $response;
    }
}
