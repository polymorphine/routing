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

    public function testRoutesMethod_ReturnsUriTemplatesAssociatedToRoutePaths()
    {
        $this->assertSame([], $this->gate()->routes('foo.bar', Doubles\FakeUri::fromString('/foo/bar')));
    }

    private function gate(Doubles\MockedRoute &$resource = null): Route\Gate\UriAttributeSelect
    {
        $resource = new Doubles\MockedRoute();
        return new Route\Gate\UriAttributeSelect($resource, 'id', 'item', 'index');
    }
}
