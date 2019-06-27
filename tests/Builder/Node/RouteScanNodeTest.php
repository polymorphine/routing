<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Builder\Node;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Builder\Node\RouteScanNode;
use Polymorphine\Routing\Builder\Node\Resource\ResourceSwitchNode;
use Polymorphine\Routing\Builder\Exception\BuilderLogicException;
use Polymorphine\Routing\Route\Splitter\RouteScan;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use InvalidArgumentException;


class RouteScanNodeTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(RouteScanNode::class, $this->builder());
    }

    public function testBuild_ReturnsResponseScanSwitch()
    {
        $this->assertInstanceOf(RouteScan::class, $this->builder()->build());
    }

    public function testRoutesCanBeAdded()
    {
        $switch = $this->builder();
        $switch->route('first')->callback($this->callbackResponse($first));
        $switch->route('second')->callback($this->callbackResponse($second));
        $route = $switch->build();

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $this->assertSame($first, $route->forward($request, $prototype));
        $this->assertSame($second, $route->select('second')->forward($request, $prototype));
    }

    public function testUnnamedRoutesCanBeAdded()
    {
        $switch = $this->builder();
        $switch->route()->method('POST')->callback($this->callbackResponse($first));
        $switch->route()->callback($this->callbackResponse($second));
        $route = $switch->build();

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $this->assertSame($first, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($second, $route->forward($request, $prototype));
    }

    public function testDefaultRouteInResponseScanSwitch()
    {
        $switch = $this->builder();
        $switch->route('dummy')->callback($this->callbackResponse($dummy));
        $switch->defaultRoute()->callback($this->callbackResponse($default));
        $route = $switch->build();

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest();
        $this->assertSame($default, $route->forward($request, $prototype));
    }

    public function testSettingDefaultRouteSecondTime_ThrowsException()
    {
        $switch = $this->builder();
        $switch->defaultRoute()->callback(function () {});
        $this->expectException(BuilderLogicException::class);
        $switch->defaultRoute();
    }

    public function testAddingRouteWithAlreadyDefinedName_ThrowsException()
    {
        $switch = $this->builder();
        $switch->route('exists')->callback(function () {});
        $this->expectException(InvalidArgumentException::class);
        $switch->route('exists');
    }

    public function testResourceBuilderCanBeAdded()
    {
        $this->assertInstanceOf(ResourceSwitchNode::class, $this->builder()->resource('res'));
    }

    public function testSeparateFormsPathForResourceBuilderCanBeSet()
    {
        $builder  = $this->builder()->withResourcesFormsPath('forms');
        $resource = $builder->resource('resource');
        $resource->add()->callback($this->callbackResponse($add));
        $resource->edit()->callback($this->callbackResponse($edit));
        $route = $builder->build();

        $responsePrototype = new FakeResponse();
        $uriPrototype      = new FakeUri();

        $newFormUri     = FakeUri::fromString('/forms/resource');
        $newFormRequest = new FakeServerRequest('GET', $newFormUri);
        $this->assertSame($add, $route->forward($newFormRequest, $responsePrototype));
        $this->assertSame((string) $newFormUri, (string) $route->select('forms.resource')->uri($uriPrototype, []));

        $editFormUri     = FakeUri::fromString('/forms/resource/997');
        $editFormRequest = new FakeServerRequest('GET', $editFormUri);
        $this->assertSame($edit, $route->forward($editFormRequest, $responsePrototype));
        $this->assertSame((string) $editFormUri, (string) $route->select('forms.resource')->uri($uriPrototype, ['resource.id' => 997]));
    }

    public function testResourceFormsPathOverwrite_ThrowsException()
    {
        $builder = $this->builder()->withResourcesFormsPath('forms');
        $this->expectException(BuilderLogicException::class);
        $builder->withResourcesFormsPath('other');
    }

    private function builder(): RouteScanNode
    {
        return new RouteScanNode();
    }
}
