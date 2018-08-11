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
use Polymorphine\Routing\Route\Endpoint\NullEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;


class NullEndpointTest extends TestCase
{
    public function testForward_ReturnsPrototype()
    {
        $endpoint  = new NullEndpoint();
        $prototype = new FakeResponse();
        $this->assertSame($prototype, $endpoint->forward(new FakeServerRequest(), $prototype));
    }
}
