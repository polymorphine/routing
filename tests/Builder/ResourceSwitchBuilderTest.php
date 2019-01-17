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
use Polymorphine\Routing\Builder\Resource\ResourceSwitchBuilder;
use Polymorphine\Routing\Builder\Resource\ContextFormsResourceSwitchBuilder;
use Polymorphine\Routing\Builder\Resource\FormsContext;
use Polymorphine\Routing\Builder\PathSwitchBuilder;
use Polymorphine\Routing\Builder\Exception\BuilderLogicException;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\UriAttributeSelect;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Polymorphine\Routing\Tests\RoutingTestMethods;


class ResourceSwitchBuilderTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(ResourceSwitchBuilder::class, $this->builder());
    }

    public function testBuild_ReturnsResponseScanSwitch()
    {
        $this->assertInstanceOf(UriAttributeSelect::class, $this->builder()->build());
    }

    public function testRoutesCanBeAdded()
    {
        $resource = $this->builder();
        $resource->index()->callback($this->callbackResponse($index));
        $resource->get()->callback($this->callbackResponse($get));
        $resource->post()->callback($this->callbackResponse($post));
        $resource->put()->callback($this->callbackResponse($put));
        $resource->patch()->callback($this->callbackResponse($patch));
        $resource->delete()->callback($this->callbackResponse($delete));
        $resource->add()->callback($this->callbackResponse($add));
        $resource->edit()->callback($this->callbackResponse($edit));
        $route = $resource->build();

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('/'));
        $this->assertSame($index, $route->forward($request, $prototype));
        $this->assertSame($post, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($add, $route->forward($request->withUri(FakeUri::fromString('new/form')), $prototype));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/3298'));
        $this->assertSame($get, $route->forward($request, $prototype));
        $this->assertSame('3298', $get->fromRequest->getAttribute('resource.id'));
        $this->assertSame($put, $route->forward($request->withMethod('PUT'), $prototype));
        $this->assertSame($patch, $route->forward($request->withMethod('PATCH'), $prototype));
        $this->assertSame($delete, $route->forward($request->withMethod('DELETE'), $prototype));
        $this->assertSame($edit, $route->forward($request->withUri(FakeUri::fromString('2398/form')), $prototype));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/foo'));
        $this->assertSame($prototype, $route->forward($request, $prototype));
    }

    public function testUriPathsForBuiltResourceRoutesIgnoreHttpMethod()
    {
        $forward  = new MockedRoute();
        $resource = $this->builder();
        $resource->get()->joinRoute($forward);
        $resource->post()->joinRoute($forward);
        $resource->index()->joinRoute($forward);
        $resource->add()->joinRoute($forward);
        $resource->edit()->joinRoute($forward);
        $route = $resource->build();

        $prototype = new FakeUri();
        $this->assertEquals('/', (string) $route->uri($prototype, []));
        $this->assertEquals('/1234', (string) $route->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/1234/form', (string) $route->select('form')->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/new/form', (string) $route->select('form')->uri($prototype, []));
        $this->assertEquals('/', (string) $route->select('index')->uri($prototype, ['resource.id' => 1234]));
    }

    public function testUriCanBeGeneratedWithoutDefined_GET_or_INDEX_Routes()
    {
        $forward  = new MockedRoute();
        $resource = $this->builder();
        $resource->delete()->joinRoute($forward);
        $resource->add()->joinRoute($forward);
        $resource->edit()->joinRoute($forward);
        $route = $resource->build();

        $prototype = new FakeUri();
        $this->assertEquals('/', (string) $route->uri($prototype, []));
        $this->assertEquals('/1234', (string) $route->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/1234/form', (string) $route->select('form')->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/new/form', (string) $route->select('form')->uri($prototype, []));
        $this->assertEquals('/', (string) $route->select('index')->uri($prototype, ['resource.id' => 1234]));
    }

    public function testSettingIdPropertiesAtAnyMoment()
    {
        $resource = $this->builder();
        $resource->get()->callback($this->callbackResponse($get));
        $resource->id('special.id', '[a-z0-9]{6}');
        $resource->patch()->callback($this->callbackResponse($patch));
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

    public function testSeparateFormRoutesCanBeDefinedWithResourceBuilder()
    {
        $resource = $this->builderWithForms($formsBuilder = new PathSwitchBuilder());

        $resource->add()->callback($this->callbackResponse($add));
        $resource->edit()->callback($this->callbackResponse($edit));
        $resource->get()->joinRoute(new MockedRoute());
        $route = $resource->build();

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('/new/form'));
        $this->assertSame($prototype, $route->forward($request, $prototype));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/123/form'));
        $this->assertSame($prototype, $route->forward($request, $prototype));

        $forms = $formsBuilder->build();
        $this->assertSame('/resource', (string) $forms->select('resource')->uri(FakeUri::fromString(''), []));
        $this->assertSame('/resource/123', (string) $forms->select('resource')->uri(FakeUri::fromString(''), ['resource.id' => '123']));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/resource'));
        $this->assertSame($add, $forms->forward($request, $prototype));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/resource/567'));
        $this->assertSame($edit, $forms->forward($request, $prototype));
        $this->assertSame('567', $edit->fromRequest->getAttribute('resource.id'));
    }

    public function testArgumentFormRoutesArePassedToSeparateContext()
    {
        $route = $this->builderWithForms($formsBuilder = new PathSwitchBuilder(), [
            'NEW'  => new Route\Endpoint\CallbackEndpoint($this->callbackResponse($add)),
            'EDIT' => new Route\Endpoint\CallbackEndpoint($this->callbackResponse($edit))
        ])->build();

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('/new/form'));
        $this->assertSame($prototype, $route->forward($request, $prototype));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/123/form'));
        $this->assertSame($prototype, $route->forward($request, $prototype));

        $forms = $formsBuilder->build();
        $this->assertSame('/resource', (string) $forms->select('resource')->uri(FakeUri::fromString(''), []));
        $this->assertSame('/resource/123', (string) $forms->select('resource')->uri(FakeUri::fromString(''), ['resource.id' => '123']));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/resource'));
        $this->assertSame($add, $forms->forward($request, $prototype));

        $request = new FakeServerRequest('GET', FakeUri::fromString('/resource/567'));
        $this->assertSame($edit, $forms->forward($request, $prototype));
        $this->assertSame('567', $edit->fromRequest->getAttribute('resource.id'));
    }

    public function testFormsRouteCanBeBuiltBeforeResourceRoutes()
    {
        $resource = $this->builderWithForms($formsBuilder = new PathSwitchBuilder());

        $resource->add()->callback($this->callbackResponse($add));
        $resource->edit()->callback($this->callbackResponse($edit));
        $resource->get()->joinRoute(new MockedRoute());
        $this->assertInstanceOf(Route::class, $formsBuilder->build());
        $this->assertInstanceOf(Route::class, $resource->build());
    }

    public function testSeparateFormsRoutesWillAllowAnyIdFormat()
    {
        $resource = $this->builderWithForms(new PathSwitchBuilder());
        $this->assertInstanceOf(ResourceSwitchBuilder::class, $resource->id('foo.id', '[a-z0-9]{3}'));
    }

    public function testIdWithRegexpMatchingNEWPseudoMethod_ThrowsException()
    {
        $resource = $this->builder();
        $this->expectException(BuilderLogicException::class);
        $resource->id('foo.id', '[a-z0-9]{3}');
    }

    private function builder(): ResourceSwitchBuilder
    {
        return new ResourceSwitchBuilder(null, []);
    }

    private function builderWithForms(PathSwitchBuilder $formsBuilder, array $routes = []): ResourceSwitchBuilder
    {
        $forms = new FormsContext('resource', $formsBuilder);
        return new ContextFormsResourceSwitchBuilder($forms, null, $routes);
    }
}
