<?php
/**
 * Created by PhpStorm.
 * User: MQs
 * Date: 09-06-2018
 * Time: 18:10
 */

namespace Polymorphine\Routing\Tests\Route\Endpoint;

use Polymorphine\Routing\Route\Endpoint;
use Polymorphine\Routing\Route\Endpoint\HandlerEndpoint;
use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;


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
