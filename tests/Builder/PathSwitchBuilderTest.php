<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Builder\Node\PathSwitchBuilder;
use Polymorphine\Routing\Builder\Node\Resource\ResourceSwitchBuilder;
use Polymorphine\Routing\Builder\Exception\BuilderLogicException;
use Polymorphine\Routing\Route\Splitter\PathSwitch;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use InvalidArgumentException;


class PathSwitchBuilderTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(PathSwitchBuilder::class, $this->builder());
    }

    public function testBuild_ReturnsResponseScanSwitch()
    {
        $this->assertInstanceOf(PathSwitch::class, $this->builder()->build());
    }

    public function testRoutesCanBeAdded()
    {
        $switch = $this->builder();
        $switch->route('first')->callback($this->callbackResponse($first));
        $switch->route('second')->callback($this->callbackResponse($second));
        $route = $switch->build();

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $this->assertSame($first, $route->forward($request->withUri(FakeUri::fromString('/first')), $prototype));
        $this->assertSame($second, $route->forward($request->withUri(FakeUri::fromString('/second')), $prototype));
    }

    public function testEmptyRouteName_ThrowsException()
    {
        $switch = $this->builder();
        $this->expectException(InvalidArgumentException::class);
        $switch->route('');
    }

    public function testRootRouteInPathSegmentSwitch()
    {
        $switch = $this->builder();
        $switch->route('dummy')->callback($this->callbackResponse($dummy));
        $switch->root()->callback($this->callbackResponse($root));
        $route = $switch->build();

        $prototype           = new FakeResponse();
        $relativeRootRequest = new FakeServerRequest('GET', FakeUri::fromString(''));
        $absoluteRootRequest = new FakeServerRequest('GET', FakeUri::fromString('/'));

        $this->assertSame($root, $route->forward($relativeRootRequest, $prototype));
        $this->assertSame($root, $route->forward($absoluteRootRequest, $prototype));
        $this->assertSame($root, $route->select(PathSwitch::ROOT_PATH)->forward($relativeRootRequest, $prototype));
        $this->assertSame($root, $route->select(PathSwitch::ROOT_PATH)->forward($absoluteRootRequest, $prototype));
    }

    public function testRootRouteWithLabel()
    {
        $switch = $this->builder();
        $switch->route('dummy')->callback($this->callbackResponse($dummy));
        $switch->root('rootLabel')->callback($this->callbackResponse($root));
        $route = $switch->build();

        $prototype           = new FakeResponse();
        $relativeRootRequest = new FakeServerRequest('GET', FakeUri::fromString(''));
        $absoluteRootRequest = new FakeServerRequest('GET', FakeUri::fromString('/'));

        $this->assertSame($root, $route->forward($relativeRootRequest, $prototype));
        $this->assertSame($root, $route->forward($absoluteRootRequest, $prototype));
        $this->assertSame($root, $route->select('rootLabel')->forward($relativeRootRequest, $prototype));
        $this->assertSame($root, $route->select('rootLabel')->forward($absoluteRootRequest, $prototype));
    }

    public function testSettingRootRouteSecondTime_ThrowsException()
    {
        $switch = $this->builder();
        $switch->root();
        $this->expectException(BuilderLogicException::class);
        $switch->root();
    }

    public function testAddingRouteWithAlreadyDefinedName_ThrowsException()
    {
        $switch = $this->builder();
        $switch->route('foo')->callback(function () {});
        $this->expectException(InvalidArgumentException::class);
        $switch->route('foo');
    }

    public function testResourceBuilderCanBeAdded()
    {
        $this->assertInstanceOf(ResourceSwitchBuilder::class, $this->builder()->resource('res'));
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

    private function builder(): PathSwitchBuilder
    {
        return new PathSwitchBuilder();
    }
}
