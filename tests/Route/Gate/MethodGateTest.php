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
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class MethodGateTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->gate());
    }

    public function testNotMatchingGateMethodRequestForward_ReturnsPrototypeInstance()
    {
        $prototype = new FakeResponse('prototype');
        $response  = $this->gate('DELETE')->forward(new FakeServerRequest('POST'), $prototype);
        $this->assertSame($prototype, $response);
    }

    public function testMatchingGateMethodRequestForward_ReturnsRouteResponse()
    {
        $prototype = new FakeResponse('prototype');
        $response  = $this->gate('POST')->forward(new FakeServerRequest('POST'), $prototype);
        $this->assertNotSame($prototype, $response);
        $this->assertSame('forwarded', (string) $response->getBody());
    }

    public function testNotMatchingAnyOfGateMethodsRequestForward_ReturnsPrototypeInstance()
    {
        $prototype = new FakeResponse('prototype');
        $response  = $this->gate('GET|POST|PUT')->forward(new FakeServerRequest('PATCH'), $prototype);
        $this->assertSame($prototype, $response);
    }

    public function testMatchingOneOfGateMethodsRequestForward_ReturnsRouteResponse()
    {
        $prototype = new FakeResponse('prototype');
        $response  = $this->gate('POST|PUT|PATCH|DELETE')->forward(new FakeServerRequest('PUT'), $prototype);
        $this->assertNotSame($prototype, $response);
        $this->assertSame('forwarded', (string) $response->getBody());
    }

    public function testSelectCallIsPassedDirectlyToNextRoute()
    {
        $route = MockedRoute::response('next');
        $this->assertSame($route, $this->gate('GET', $route)->select('some.path'));
        $this->assertSame('some.path', $route->path);
    }

    public function testUriCallIsPassedDirectlyToNextRoute()
    {
        $route = MockedRoute::withUri('next');
        $this->assertSame('next', $this->gate('GET', $route)->uri(new FakeUri(), [])->getPath());
    }

    private function gate(string $methods = 'GET', Route $route = null)
    {
        return new MethodGate($methods, $route ?? MockedRoute::response('forwarded'));
    }
}
