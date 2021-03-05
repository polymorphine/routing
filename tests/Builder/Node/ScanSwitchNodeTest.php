<?php declare(strict_types=1);

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
use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Exception;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Tests\Builder;
use Polymorphine\Routing\Tests\Doubles;
use Polymorphine\Routing\Tests\RoutingTestMethods;


class ScanSwitchNodeTest extends TestCase
{
    use RoutingTestMethods;
    use Builder\ContextCreateMethod;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Node\ScanSwitchNode::class, $this->builder());
    }

    public function testBuild_ReturnsResponseScanSwitch()
    {
        $this->assertInstanceOf(Route\Splitter\ScanSwitch::class, $this->builder()->build());
    }

    public function testRoutesCanBeAdded()
    {
        $switch = $this->builder();
        $switch->route('first')->callback($this->callbackResponse($first));
        $switch->route('second')->callback($this->callbackResponse($second));
        $route = $switch->build();

        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($first, $route->forward($request, $prototype));
        $this->assertSame($second, $route->select('second')->forward($request, $prototype));
    }

    public function testUnnamedRoutesCanBeAdded()
    {
        $switch = $this->builder();
        $switch->route()->method('POST')->callback($this->callbackResponse($first));
        $switch->route()->callback($this->callbackResponse($second));
        $route = $switch->build();

        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($first, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($second, $route->forward($request, $prototype));
    }

    public function testDefaultRouteInResponseScanSwitch()
    {
        $switch = $this->builder();
        $switch->route('dummy')->callback($this->callbackResponse($dummy));
        $switch->defaultRoute()->callback($this->callbackResponse($default));
        $route = $switch->build();

        $prototype = new Doubles\FakeResponse();
        $request   = new Doubles\FakeServerRequest();
        $this->assertSame($default, $route->forward($request, $prototype));
    }

    public function testSettingDefaultRouteSecondTime_ThrowsException()
    {
        $switch = $this->builder();
        $switch->defaultRoute()->callback(function () {});
        $this->expectException(Exception\BuilderLogicException::class);
        $switch->defaultRoute();
    }

    public function testAddingRouteWithAlreadyDefinedName_ThrowsException()
    {
        $switch = $this->builder();
        $switch->route('exists')->callback(function () {});
        $this->expectException(Exception\BuilderLogicException::class);
        $switch->route('exists');
    }

    public function testResourceBuilderCanBeAdded()
    {
        $this->assertInstanceOf(Node\Resource\ResourceSwitchNode::class, $this->builder()->resource('res'));
    }

    public function testSeparateFormsPathForResourceBuilderCanBeSet()
    {
        $builder  = $this->builder()->withResourcesFormsPath('forms');
        $resource = $builder->resource('resource');
        $resource->add()->callback($this->callbackResponse($add));
        $resource->edit()->callback($this->callbackResponse($edit));
        $route = $builder->build();

        $responsePrototype = new Doubles\FakeResponse();
        $uriPrototype      = new Doubles\FakeUri();

        $newFormUri     = Doubles\FakeUri::fromString('/forms/resource');
        $newFormRequest = new Doubles\FakeServerRequest('GET', $newFormUri);
        $this->assertSame($add, $route->forward($newFormRequest, $responsePrototype));
        $this->assertSame((string) $newFormUri, (string) $route->select('forms.resource')->uri($uriPrototype, []));

        $editFormUri     = Doubles\FakeUri::fromString('/forms/resource/997');
        $editFormRequest = new Doubles\FakeServerRequest('GET', $editFormUri);
        $this->assertSame($edit, $route->forward($editFormRequest, $responsePrototype));
        $this->assertSame((string) $editFormUri, (string) $route->select('forms.resource')->uri($uriPrototype, ['resource.id' => 997]));
    }

    public function testResourceFormsPathOverwrite_ThrowsException()
    {
        $builder = $this->builder()->withResourcesFormsPath('forms');
        $this->expectException(Exception\BuilderLogicException::class);
        $builder->withResourcesFormsPath('other');
    }

    private function builder(): Node\ScanSwitchNode
    {
        return new Node\ScanSwitchNode($this->context());
    }
}
