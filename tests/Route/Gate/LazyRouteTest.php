<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Gate;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\LazyRoute;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class LazyRouteTest extends TestCase
{
    private $invokedRoute;

    public function setUp()
    {
        $this->invokedRoute = null;
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testWrappedRouteInstantiationIsDeferred()
    {
        $route = $this->route();
        $this->assertNull($this->invokedRoute);
        $route->forward(new FakeServerRequest(), new FakeResponse());
        $this->assertInstanceOf(Route::class, $this->invokedRoute);
    }

    public function testUriIsCalledOnInvokedRoute()
    {
        $uri = $this->route()->uri(new FakeUri(), []);
        $this->assertSame('invoked', $uri->getPath());
    }

    public function testRequestIsPassedToInvokedRoute()
    {
        $response = $this->route()->forward(new FakeServerRequest(), new FakeResponse());
        $this->assertSame('invoked', $response->body);
    }

    public function testSelectIsCalledOnInvokedRoute()
    {
        $route = $this->route()->select('invoked.route.path');
        $this->assertSame('invoked.route.path', $route->path);
    }

    private function route()
    {
        return new LazyRoute(function () {
            return $this->invokedRoute = new MockedRoute(new FakeResponse('invoked'), FakeUri::fromString('invoked'));
        });
    }
}
