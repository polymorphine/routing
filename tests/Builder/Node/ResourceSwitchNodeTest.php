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
use Polymorphine\Routing\Tests;
use Polymorphine\Routing\Tests\Doubles;


class ResourceSwitchNodeTest extends TestCase
{
    use Tests\RoutingTestMethods;
    use Tests\Builder\ContextCreateMethod;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Node\Resource\ResourceSwitchNode::class, $this->builder());
    }

    public function testBuild_ReturnsMethodSwitch()
    {
        $this->assertInstanceOf(Route\Splitter\MethodSwitch::class, $this->builder()->build());
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

        $prototype = new Doubles\FakeResponse();
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/'));
        $this->assertSame($index, $route->forward($request, $prototype));
        $this->assertSame($post, $route->forward($request->withMethod('POST'), $prototype));
        $this->assertSame($add, $route->forward($request->withUri(Doubles\FakeUri::fromString('new/form')), $prototype));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/3298'));
        $this->assertSame($get, $route->forward($request, $prototype));
        $this->assertSame('3298', $get->fromRequest->getAttribute('resource.id'));
        $this->assertSame($put, $route->forward($request->withMethod('PUT'), $prototype));
        $this->assertSame($patch, $route->forward($request->withMethod('PATCH'), $prototype));
        $this->assertSame($delete, $route->forward($request->withMethod('DELETE'), $prototype));
        $this->assertSame($edit, $route->forward($request->withUri(Doubles\FakeUri::fromString('2398/form')), $prototype));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/foo'));
        $this->assertSame($prototype, $route->forward($request, $prototype));
    }

    public function testUriPathsForBuiltResourceRoutesIgnoreHttpMethod()
    {
        $forward  = new Doubles\MockedRoute();
        $resource = $this->builder();
        $resource->get()->joinRoute($forward);
        $resource->post()->joinRoute($forward);
        $resource->index()->joinRoute($forward);
        $resource->add()->joinRoute($forward);
        $resource->edit()->joinRoute($forward);
        $route = $resource->build();

        $prototype = new Doubles\FakeUri();
        $this->assertEquals('/', (string) $route->uri($prototype, []));
        $this->assertEquals('/1234', (string) $route->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/1234/form', (string) $route->select('form')->uri($prototype, ['resource.id' => 1234]));
        $this->assertEquals('/new/form', (string) $route->select('form')->uri($prototype, []));
        $this->assertEquals('/', (string) $route->select('index')->uri($prototype, ['resource.id' => 1234]));
    }

    public function testUriCanBeGeneratedWithoutDefined_GET_or_INDEX_Routes()
    {
        $forward  = new Doubles\MockedRoute();
        $resource = $this->builder();
        $resource->delete()->joinRoute($forward);
        $resource->add()->joinRoute($forward);
        $resource->edit()->joinRoute($forward);
        $route = $resource->build();

        $prototype = new Doubles\FakeUri();
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

        $prototype = new Doubles\FakeResponse();
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('abc012'));
        $this->assertSame($get, $route->forward($request, $prototype));
        $this->assertSame('abc012', $get->fromRequest->getAttribute('special.id'));

        $request = new Doubles\FakeServerRequest('PATCH', Doubles\FakeUri::fromString('09a0bc'));
        $this->assertSame($patch, $route->forward($request, $prototype));
        $this->assertSame('09a0bc', $patch->fromRequest->getAttribute('special.id'));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('abc'));
        $this->assertSame($prototype, $route->forward($request, $prototype));
    }

    public function testSeparateFormRoutesCanBeDefinedWithResourceBuilder()
    {
        /** @var Node\PathSwitchNode $formsBuilder */
        $resource = $this->builderWithForms($formsBuilder);

        $resource->add()->callback($this->callbackResponse($add));
        $resource->edit()->callback($this->callbackResponse($edit));
        $resource->get()->joinRoute(new Doubles\MockedRoute());
        $route = $resource->build();

        $prototype = new Doubles\FakeResponse();
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/new/form'));
        $this->assertSame($prototype, $route->forward($request, $prototype));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/123/form'));
        $this->assertSame($prototype, $route->forward($request, $prototype));

        $forms = $formsBuilder->build();
        $this->assertSame('/resource', (string) $forms->select('resource')->uri(Doubles\FakeUri::fromString(''), []));
        $this->assertSame('/resource/123', (string) $forms->select('resource')->uri(Doubles\FakeUri::fromString(''), ['resource.id' => '123']));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/resource'));
        $this->assertSame($add, $forms->forward($request, $prototype));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/resource/567'));
        $this->assertSame($edit, $forms->forward($request, $prototype));
        $this->assertSame('567', $edit->fromRequest->getAttribute('resource.id'));
    }

    public function testArgumentFormRoutesArePassedToSeparateContext()
    {
        /** @var Node\PathSwitchNode $formsBuilder */
        $route = $this->builderWithForms($formsBuilder, [
            'NEW'  => new Route\Endpoint\CallbackEndpoint($this->callbackResponse($add)),
            'EDIT' => new Route\Endpoint\CallbackEndpoint($this->callbackResponse($edit))
        ])->build();

        $prototype = new Doubles\FakeResponse();
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/new/form'));
        $this->assertSame($prototype, $route->forward($request, $prototype));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/123/form'));
        $this->assertSame($prototype, $route->forward($request, $prototype));

        $forms = $formsBuilder->build();
        $this->assertSame('/resource', (string) $forms->select('resource')->uri(Doubles\FakeUri::fromString(''), []));
        $this->assertSame('/resource/123', (string) $forms->select('resource')->uri(Doubles\FakeUri::fromString(''), ['resource.id' => '123']));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/resource'));
        $this->assertSame($add, $forms->forward($request, $prototype));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/resource/567'));
        $this->assertSame($edit, $forms->forward($request, $prototype));
        $this->assertSame('567', $edit->fromRequest->getAttribute('resource.id'));
    }

    public function testFormsRouteCanBeBuiltBeforeResourceRoutes()
    {
        /** @var Node\PathSwitchNode $formsBuilder */
        $resource = $this->builderWithForms($formsBuilder);

        $resource->add()->callback($this->callbackResponse($add));
        $resource->edit()->callback($this->callbackResponse($edit));
        $resource->get()->joinRoute(new Doubles\MockedRoute());
        $this->assertInstanceOf(Route::class, $formsBuilder->build());
        $this->assertInstanceOf(Route::class, $resource->build());
    }

    public function testSeparateFormsRoutesWillAllowAnyIdFormat()
    {
        $resource = $this->builderWithForms($formsBuilder);
        $this->assertInstanceOf(Node\Resource\ResourceSwitchNode::class, $resource->id('foo.id', '[a-z0-9]{3}'));
    }

    public function testIdWithRegexpMatchingNEWPseudoMethod_ThrowsException()
    {
        $resource = $this->builder();
        $this->expectException(Exception\BuilderLogicException::class);
        $resource->id('foo.id', '[a-z0-9]{3}');
    }

    private function builder(): Node\Resource\ResourceSwitchNode
    {
        return new Node\Resource\ResourceSwitchNode($this->context());
    }

    private function builderWithForms(?Node\PathSwitchNode &$formsBuilder, array $routes = []): Node\Resource\ResourceSwitchNode
    {
        $formsBuilder = new Node\PathSwitchNode($this->context());
        $forms        = new Node\Resource\FormsContext('resource', $formsBuilder);
        return new Node\Resource\LinkedFormsResourceSwitchNode($forms, $this->context(), $routes);
    }
}
