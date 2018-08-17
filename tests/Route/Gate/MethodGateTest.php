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
use Polymorphine\Routing\Route\Gate\MethodGate;
use Polymorphine\Routing\Tests\EndpointTestMethods;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class MethodGateTest extends TestCase
{
    use EndpointTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->gate());
    }

    public function testNotMatchingGateMethodRequestForward_ReturnsPrototypeInstance()
    {
        $route = $this->gate('DELETE');
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest('POST'), self::$prototype));
    }

    public function testMatchingGateMethodRequestForward_ReturnsRouteResponse()
    {
        $route = $this->gate('POST');
        $this->assertNotSame(self::$prototype, $route->forward(new FakeServerRequest('POST'), self::$prototype));
    }

    public function testNotMatchingAnyOfGateMethodsRequestForward_ReturnsPrototypeInstance()
    {
        $route = $this->gate('GET|POST|PUT');
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest('PATCH'), self::$prototype));
    }

    public function testMatchingOneOfGateMethodsRequestForward_ReturnsRouteResponse()
    {
        $route = $this->gate('POST|PUT|PATCH|DELETE');
        $this->assertNotSame(self::$prototype, $route->forward(new FakeServerRequest('PUT'), self::$prototype));
    }

    public function testSelectCallIsPassedDirectlyToNextRoute()
    {
        $route = $this->gate('GET', $next = MockedRoute::response('next'));
        $this->assertSame($next, $route->select('some.path'));
        $this->assertSame('some.path', $next->path);
    }

    public function testUriCallIsPassedDirectlyToNextRoute()
    {
        $route = $this->gate('GET', MockedRoute::withUri('next'));
        $this->assertSame('next', $route->uri(new FakeUri(), [])->getPath());
    }

    private function gate(string $methods = 'GET', Route $route = null)
    {
        return new MethodGate($methods, $route ?? MockedRoute::response('forwarded'));
    }
}
