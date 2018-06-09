<?php
/**
 * Created by PhpStorm.
 * User: MQs
 * Date: 09-06-2018
 * Time: 17:03
 */

namespace Polymorphine\Routing\Tests\Route;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Exception\SwitchCallException;
use Polymorphine\Routing\Route\Endpoint;
use Polymorphine\Routing\Tests\Doubles\DummyEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class EndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Endpoint::class, new DummyEndpoint());
    }

    public function testRoute_ThrowsException()
    {
        $route = new DummyEndpoint();
        $this->expectException(SwitchCallException::class);
        $route->route('foo');
    }

    public function testUri_ReturnsPrototype()
    {
        $route = new DummyEndpoint();
        $uri   = new FakeUri();
        $this->assertSame($uri, $route->uri($uri, []));
    }
}
