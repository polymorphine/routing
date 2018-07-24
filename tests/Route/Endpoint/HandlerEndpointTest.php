<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Endpoint;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route\Endpoint;
use Polymorphine\Routing\Route\Endpoint\HandlerEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;


class HandlerEndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Endpoint::class, new HandlerEndpoint(new FakeRequestHandler(new FakeResponse())));
    }

    public function testForward_ReturnsHandlerResponse()
    {
        $response = new FakeResponse('processed');
        $endpoint = new HandlerEndpoint(new FakeRequestHandler($response));
        $this->assertSame($response, $endpoint->forward(new FakeServerRequest(), new FakeResponse()));
    }
}
