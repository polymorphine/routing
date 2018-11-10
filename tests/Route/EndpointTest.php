<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Endpoint;
use Polymorphine\Routing\Exception\SwitchCallException;
use Polymorphine\Routing\Tests\Doubles\DummyEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class EndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Endpoint::class, new DummyEndpoint());
    }

    public function testSelectCall_ThrowsException()
    {
        $route = new DummyEndpoint();
        $this->expectException(SwitchCallException::class);
        $route->select('foo');
    }

    public function testUriCall_ReturnsPrototype()
    {
        $route = new DummyEndpoint();
        $uri   = new FakeUri();
        $this->assertSame($uri, $route->uri($uri, []));
    }

    public function testOptionsMethod_ReturnsAllowedMethodsHeader()
    {
        $route   = new DummyEndpoint();
        $methods = ['GET', 'POST', 'DELETE'];

        $request = (new FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methods);
        $this->assertSame([implode(', ', $methods)], $route->forward($request, new FakeResponse())->getHeader('Allow'));
    }
}
