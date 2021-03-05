<?php declare(strict_types=1);

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
use Polymorphine\Routing\Map;
use Polymorphine\Routing\Tests\Doubles;


class LazyRouteTest extends TestCase
{
    private $invokedRoute;

    public function setUp(): void
    {
        $this->invokedRoute = null;
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->gate());
    }

    public function testWrappedRouteInstantiationIsDeferred()
    {
        $gate = $this->gate($route);
        $this->assertNull($route);
        $gate->forward(new Doubles\FakeServerRequest(), new Doubles\FakeResponse());
        $this->assertInstanceOf(Route::class, $route);
    }

    public function testRequestIsPassedToInvokedRoute()
    {
        $request  = new Doubles\FakeServerRequest();
        $response = $this->gate($route)->forward($request, new Doubles\FakeResponse());
        $this->assertSame($response, $route->response);
    }

    public function testUriIsCalledOnInvokedRoute()
    {
        $uri = $this->gate($route)->uri(new Doubles\FakeUri(), []);
        $this->assertSame($uri, $route->uri);
    }

    public function testSelectIsCalledOnInvokedRoute()
    {
        $this->gate($route)->select('invoked.route.path');
        $this->assertSame('invoked.route.path', $route->path);
    }

    public function testRoutesMethod_PassesTraceToNextRoute()
    {
        $trace = new Map\Trace(new Map(), new Doubles\FakeUri());
        $this->gate($route)->routes($trace);
        $this->assertSame($trace, $route->trace);
    }

    private function gate(?Route &$route = null)
    {
        return new Route\Gate\LazyRoute(function () use (&$route) {
            return $route = new Doubles\MockedRoute(
                new Doubles\FakeResponse('invoked'),
                Doubles\FakeUri::fromString('invoked')
            );
        });
    }
}
