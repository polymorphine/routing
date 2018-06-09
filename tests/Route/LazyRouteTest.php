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
use Polymorphine\Routing\Route\LazyRoute;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class LazyRouteTest extends TestCase
{
    private $route;

    public function setUp()
    {
        $this->route = null;
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());
        $this->assertNull($this->route);
    }

    public function testUriIsCalledOnInvokedRoute()
    {
        $uri = $this->route()->uri(new FakeUri(), []);
        $this->assertInstanceOf(Route::class, $this->route);
        $this->assertSame('invoked', $uri->getPath());
    }

    public function testRequestIsPassedToInvokedRoute()
    {
        $response = $this->route()->forward(new FakeServerRequest(), new FakeResponse());
        $this->assertInstanceOf(Route::class, $this->route);
        $this->assertSame('invoked', $response->body);
    }

    public function testGatewayIsCalledOnInvokedRoute()
    {
        $route = $this->route()->route('invoked.route.path');
        $this->assertInstanceOf(Route::class, $this->route);
        $this->assertSame('invoked.route.path', $route->path);
    }

    private function route()
    {
        return new LazyRoute(function () {
            return $this->route = new MockedRoute('invoked');
        });
    }
}
