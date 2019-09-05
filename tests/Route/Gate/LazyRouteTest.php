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
use Polymorphine\Routing\Tests\Doubles;


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
        $route->forward(new Doubles\FakeServerRequest(), new Doubles\FakeResponse());
        $this->assertInstanceOf(Route::class, $this->invokedRoute);
    }

    public function testUriIsCalledOnInvokedRoute()
    {
        $uri = $this->route()->uri(new Doubles\FakeUri(), []);
        $this->assertSame('invoked', $uri->getPath());
    }

    public function testRequestIsPassedToInvokedRoute()
    {
        $response = $this->route()->forward(new Doubles\FakeServerRequest(), new Doubles\FakeResponse());
        $this->assertSame('invoked', $response->body);
    }

    public function testSelectIsCalledOnInvokedRoute()
    {
        $route = $this->route()->select('invoked.route.path');
        $this->assertSame('invoked.route.path', $route->path);
    }

    public function testRoutesMethod_ReturnsUriTemplatesAssociatedToRoutePaths()
    {
        $this->assertSame([], $this->route()->routes('foo.bar', Doubles\FakeUri::fromString('/foo/bar')));
    }

    private function route()
    {
        return new Route\Gate\LazyRoute(function () {
            return $this->invokedRoute = new Doubles\MockedRoute(
                new Doubles\FakeResponse('invoked'),
                Doubles\FakeUri::fromString('invoked')
            );
        });
    }
}
