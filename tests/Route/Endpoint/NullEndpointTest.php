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


class NullEndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Endpoint::class, new Endpoint\NullEndpoint());
    }

    public function testForward_ReturnsPrototype()
    {
        $endpoint  = new Endpoint\NullEndpoint();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $endpoint->forward(new Doubles\FakeServerRequest(), $prototype));
    }
}
