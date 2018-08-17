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
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;


class CallbackEndpointTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Endpoint::class, new CallbackEndpoint(function () {}));
    }

    public function testForward_ReturnsCallbackResponse()
    {
        $endpoint = new CallbackEndpoint($this->callbackResponse($response));
        $this->assertSame($response, $endpoint->forward(new FakeServerRequest(), new FakeResponse()));
    }
}
