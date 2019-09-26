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
use Polymorphine\Routing\Map;
use Polymorphine\Routing\Tests\Doubles;


class UriAttributeSelectTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route\Gate\UriAttributeSelect::class, $gate = $this->gate());
        $this->assertInstanceOf(Route::class, $gate);
    }

    public function testRequestIsForwardedToResourceRoute()
    {
        $request  = new Doubles\FakeServerRequest();
        $response = $this->gate($resource)->forward($request, new Doubles\FakeResponse());
        $this->assertSame($response, $resource->response);
        $this->assertSame($request, $resource->forwardedRequest);
    }

    public function testSelectMethod_SelectsResourceRoute()
    {
        $selected = $this->gate($resource)->select('some.path');
        $this->assertSame('some.path', $resource->path);
        $this->assertSame($selected, $resource->subRoute);
    }

    public function testUriIsSelectedFromIndexResourceRouteBasedOnIdParam()
    {
        $gate      = $this->gate($resource);
        $prototype = new Doubles\FakeUri();
        $this->assertSame($prototype, $gate->uri($prototype, ['notId' => 'something']));
        $this->assertSame('index', $resource->path);

        $this->assertSame($prototype, $gate->uri($prototype, ['id' => 'something']));
        $this->assertSame('item', $resource->path);
    }

    public function testRoutesMethod_PassesTraceToNextRoute()
    {
        $trace = new Route\Trace(new Map(), new Doubles\FakeUri());
        $this->gate($route)->routes($trace);
        $this->assertSame($trace, $route->trace);
    }

    private function gate(Doubles\MockedRoute &$resource = null): Route\Gate\UriAttributeSelect
    {
        $resource = new Doubles\MockedRoute(new Doubles\FakeResponse(), new Doubles\FakeUri());
        return new Route\Gate\UriAttributeSelect($resource, 'id', 'item', 'index');
    }
}
