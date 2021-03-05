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
use Polymorphine\Routing\Tests\Doubles;


class HandlerEndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $instance = new Endpoint\HandlerEndpoint(new Doubles\FakeRequestHandler(new Doubles\FakeResponse()));
        $this->assertInstanceOf(Endpoint::class, $instance);
    }

    public function testForward_ReturnsHandlerResponse()
    {
        $endpoint = new Endpoint\HandlerEndpoint(new Doubles\FakeRequestHandler($response = new Doubles\FakeResponse()));
        $this->assertSame($response, $endpoint->forward(new Doubles\FakeServerRequest(), new Doubles\FakeResponse()));
    }
}
