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
use Polymorphine\Routing\Builder\ResourceSwitchBuilder;
use Polymorphine\Routing\Builder\SwitchBuilder;
use Polymorphine\Routing\Builder\PathSegmentSwitchBuilder;
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Route\Splitter\PathSegmentSwitch;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use InvalidArgumentException;


class PathSegmentSwitchBuilderTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(SwitchBuilder::class, $this->builder());
    }

    public function testBuild_ReturnsResponseScanSwitch()
    {
        $this->assertInstanceOf(PathSegmentSwitch::class, $this->builder()->build());
    }

    public function testRoutesCanBeAdded()
    {
        $switch = $this->builder();
        $switch->route('first')->callback($this->responseCallback($first));
        $switch->route('second')->callback($this->responseCallback($second));
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
        $switch->route('dummy')->callback($this->responseCallback($dummy));
        $switch->root(new CallbackEndpoint($this->responseCallback($root)));
        $route = $switch->build();

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest();
        $this->assertSame($root, $route->forward($request->withUri(FakeUri::fromString('')), $prototype));
        $this->assertSame($root, $route->forward($request->withUri(FakeUri::fromString('/')), $prototype));
    }

    //TODO: testSettingRootRouteSecondTime_ThrowsException()

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

    private function responseCallback(&$response)
    {
        $response = new FakeResponse();
        return function () use (&$response) {
            return $response;
        };
    }

    private function builder(): PathSegmentSwitchBuilder
    {
        return new PathSegmentSwitchBuilder();
    }
}
