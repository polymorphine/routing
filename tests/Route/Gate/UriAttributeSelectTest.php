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


class UriAttributeSelectTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route\Gate\UriAttributeSelect::class, $this->gate());
        $this->assertInstanceOf(Route::class, $this->gate());
    }

    public function testRequestIsForwardedToResourceRoute()
    {
        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $this->gate($resource)->forward($request, $prototype));
        $this->assertSame($request, $resource->forwardedRequest);
    }

    public function testSelectMethod_SelectsResourceRoute()
    {
        $gate = $this->gate($resource);
        $this->assertSame($resource, $gate->select('some.path'));
        $this->assertSame('some.path', $resource->path);
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

    private function gate(Doubles\MockedRoute &$resource = null): Route\Gate\UriAttributeSelect
    {
        $resource = new Doubles\MockedRoute();
        return new Route\Gate\UriAttributeSelect($resource, 'id', 'item', 'index');
    }
}
