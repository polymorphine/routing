<?php declare(strict_types=1);

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
use Polymorphine\Routing\Tests;


class CallbackEndpointTest extends TestCase
{
    use Tests\RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Endpoint::class, new Endpoint\CallbackEndpoint(function () {}));
    }

    public function testForward_ReturnsCallbackResponse()
    {
        $endpoint = new Endpoint\CallbackEndpoint($this->callbackResponse($response));
        $this->assertSame($response, $endpoint->forward(new Tests\Doubles\FakeServerRequest(), new Tests\Doubles\FakeResponse()));
    }
}
