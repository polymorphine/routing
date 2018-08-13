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
use Polymorphine\Routing\Builder\SwitchBuilder;
use Polymorphine\Routing\Builder\ResourceSwitchBuilder;
use Polymorphine\Routing\Builder\Exception\BuilderLogicException;
use Polymorphine\Routing\Route\Gate\ResourceGateway;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Polymorphine\Routing\Tests\EndpointTestMethods;
use InvalidArgumentException;


class ResourceSwitchBuilderTest extends TestCase
{
    use EndpointTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(SwitchBuilder::class, $this->builder());
    }

    public function testBuild_ReturnsResponseScanSwitch()
    {
        $this->assertInstanceOf(ResourceGateway::class, $this->builder()->build());
    }

    public function testRoutesCanBeAdded()
    {
        $resource = $this->builder();
        $resource->route('INDEX')->callback($this->callbackResponse($index));
        $resource->route('GET')->callback($this->callbackResponse($get));
        $resource->route('POST')->callback($this->callbackResponse($post));
        $route = $resource->build();

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('/'));
        $this->assertSame($index, $route->forward($request, $prototype));
        $this->assertSame($post, $route->forward($request->withMethod('POST'), $prototype));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/3298'));
        $this->assertSame($get, $route->forward($request, $prototype));
        $this->assertSame('3298', $get->fromRequest->getAttribute('resource.id'));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/foo'));
        $this->assertSame($prototype, $route->forward($request, $prototype));
    }

    public function testEmptyRouteName_ThrowsException()
    {
        $resource = $this->builder();
        $this->expectException(InvalidArgumentException::class);
        $resource->route('');
    }

    public function testInvalidMethodNameForResourceRoute_ThrowsException()
    {
        $resource = $this->builder();
        $this->expectException(InvalidArgumentException::class);
        $resource->route('FOO');
    }

    public function testUriPathsForBuiltResourceRoutesIgnoreHttpMethod()
    {
        $forward  = new MockedRoute();
        $resource = $this->builder();
        $resource->route('GET')->join($forward);
        $resource->route('POST')->join($forward);
        $resource->route('INDEX')->join($forward);
        $resource->route('NEW')->join($forward);
        $resource->route('EDIT')->join($forward);
        $route = $resource->build();

        $prototype = new FakeUri();
        $this->assertEquals('/', (string) $route->uri($prototype, []));
        $this->assertEquals('/1234', (string) $route->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/1234/form', (string) $route->select('edit')->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/1234/form', (string) $route->select('form')->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/new/form', (string) $route->select('new')->uri($prototype, []));
        $this->assertEquals('/new/form', (string) $route->select('form')->uri($prototype, []));
        $this->assertEquals('/', (string) $route->select('index')->uri($prototype, ['resource.id' => 1234]));
    }

    public function testUriCanBeGeneratedWithoutDefined_GET_or_INDEX_Routes()
    {
        $forward  = new MockedRoute();
        $resource = $this->builder();
        $resource->route('DELETE')->join($forward);
        $resource->route('NEW')->join($forward);
        $resource->route('EDIT')->join($forward);
        $route = $resource->build();

        $prototype = new FakeUri();
        $this->assertEquals('/', (string) $route->uri($prototype, []));
        $this->assertEquals('/1234', (string) $route->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/1234/form', (string) $route->select('form')->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/new/form', (string) $route->select('form')->uri($prototype, []));
        $this->assertEquals('/', (string) $route->select('index')->uri($prototype, ['resource.id' => 1234]));
    }

    public function testRequestFormsForwardedToCorrectEndpoint()
    {
        $resource = $this->builder();
        $resource->route('INDEX')->callback($this->callbackResponse($index));
        $resource->route('GET')->callback($this->callbackResponse($get));
        $resource->route('NEW')->callback($this->callbackResponse($new));
        $resource->route('EDIT')->callback($this->callbackResponse($edit));
        $route = $resource->build();

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('/new/form'));
        $this->assertSame($new, $route->forward($request, $prototype));

        $request   = new FakeServerRequest('GET', FakeUri::fromString('/123/form'));
        $this->assertSame($edit, $route->forward($request, $prototype));
    }

    public function testSettingIdPropertiesAtAnyMoment()
    {
        $resource = $this->builder();
        $resource->route('GET')->callback($this->callbackResponse($get));
        $resource->id('special.id', '[a-z0-9]{6}');
        $resource->route('PATCH')->callback($this->callbackResponse($patch));
        $route = $resource->build();

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('abc012'));
        $this->assertSame($get, $route->forward($request, $prototype));
        $this->assertSame('abc012', $get->fromRequest->getAttribute('special.id'));

        $request = new FakeServerRequest('PATCH', FakeUri::fromString('09a0bc'));
        $this->assertSame($patch, $route->forward($request, $prototype));
        $this->assertSame('09a0bc', $patch->fromRequest->getAttribute('special.id'));

        $request = new FakeServerRequest('GET', FakeUri::fromString('abc'));
        $this->assertSame($prototype, $route->forward($request, $prototype));
    }

    public function testIdWithRegexpMatchingNEWPseudoMethod_ThrowsException()
    {
        $resource = $this->builder();
        $this->expectException(BuilderLogicException::class);
        $resource->id('foo.id', '[a-z0-9]{3}');
    }

    private function builder(): ResourceSwitchBuilder
    {
        return new ResourceSwitchBuilder();
    }
}
