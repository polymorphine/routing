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


class WildcardPathGateTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route\Gate\WildcardPathGate::class, $this->gate());
    }

    public function testRequestIsForwardedWithWildcardAttribute()
    {
        $this->gate($route)->forward($this->request(false), new Doubles\FakeResponse());
        $this->assertTrue($route->forwardedRequest->getAttribute(Route::WILDCARD_ATTRIBUTE));
    }

    public function testSelectMethod_ReturnsSelectedRouteWrappedWithWildcardGate()
    {
        $originalRoute = $this->gate($route);
        $selectedRoute = $originalRoute->select('some.route.path');

        $this->assertSame('some.route.path', $route->path);
        $this->assertInstanceOf(Route\Gate\WildcardPathGate::class, $selectedRoute);
        $this->assertEquals($selectedRoute, $originalRoute);
    }

    public function testUriMethod_ReturnsUriFromWrappedRoute()
    {
        $route     = Doubles\MockedRoute::withUri(Doubles\FakeUri::fromString('/some/route/path'));
        $prototype = Doubles\FakeUri::fromString('http://example.com');
        $this->assertSame('http://example.com/some/route/path', (string) $this->gate($route)->uri($prototype, []));
    }

    private function gate(Doubles\MockedRoute &$route = null)
    {
        return new Route\Gate\WildcardPathGate($route ?? $route = new Doubles\MockedRoute());
    }

    private function request(bool $wildcard = false)
    {
        $request = new Doubles\FakeServerRequest();
        return $wildcard ? $request->withAttribute(Route::WILDCARD_ATTRIBUTE, true) : $request;
    }
}
