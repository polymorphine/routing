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
use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Exception;
use Polymorphine\Routing\Builder\NodeContext;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\LazyRoute;
use Polymorphine\Routing\Route\Gate\Pattern\UriPattern;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\Scheme;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\Path;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Route\Endpoint\HandlerEndpoint;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Tests\Doubles\FakeContainer;
use Polymorphine\Routing\Tests\Doubles\FakeHandlerFactory;
use Polymorphine\Routing\Tests\Doubles\FakeMiddleware;
use Polymorphine\Routing\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\RoutingTestMethods;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;


class ContextRouteBuilderTest extends TestCase
{
    use RoutingTestMethods;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Node\ContextRouteNode::class, $this->builder());
    }

    public function testRouteCanBeSplit()
    {
        $this->assertInstanceOf(Node\RouteScanNode::class, $this->builder()->responseScan());
        $this->assertInstanceOf(Node\MethodSwitchNode::class, $this->builder()->methodSwitch());
        $this->assertInstanceOf(Node\PathSwitchNode::class, $this->builder()->pathSwitch());
        $this->assertInstanceOf(Node\Resource\ResourceSwitchNode::class, $this->builder()->resource());
    }

    public function testCallbackEndpoint()
    {
        $builder = $this->builder();
        $builder->callback(function () {});
        $this->assertInstanceOf(CallbackEndpoint::class, $builder->build());
    }

    public function testHandlerEndpoint()
    {
        $builder = $this->builder();
        $builder->handler(new FakeRequestHandler(new FakeResponse()));
        $this->assertInstanceOf(HandlerEndpoint::class, $builder->build());
    }

    public function testLazyEndpoint()
    {
        $builder = $this->builder();
        $builder->lazy(function () {});
        $this->assertInstanceOf(LazyRoute::class, $builder->build());
    }

    public function testSetRouteWhenAlreadyBuilt_ThrowsException()
    {
        $route = $this->builder();
        $route->callback(function () {});
        $this->expectException(Exception\BuilderLogicException::class);
        $route->pathSwitch();
    }

    public function testBuildUndefinedRoute_ThrowsException()
    {
        $builder = $this->builder();
        $this->expectException(Exception\BuilderLogicException::class);
        $builder->build();
    }

    public function testGateWrappers()
    {
        $attrCheckCallback = function (ServerRequestInterface $request) {
            return $request->getAttribute('test') ? $request : null;
        };

        $request = new FakeServerRequest('GET', FakeUri::fromString('http://example.com/foo'));
        $https   = FakeUri::fromString('https://example.com/foo/bar/baz');

        $cases = [
            [$this->builder()->pattern(new Scheme('https')), $request->withUri($https), $request],
            [$this->builder()->path('foo/bar/baz'), $request->withUri($https), $request],
            [$this->builder()->path('foo/{@name}/baz'), $request->withUri($https), $request],
            [$this->builder()->path('foo/{name}/baz', ['name' => 'b.r']), $request->withUri($https), $request],
            [$this->builder()->callbackGate($attrCheckCallback), $request->withAttribute('test', true), $request],
            [$this->builder()->method('PATCH'), $request->withMethod('PATCH'), $request],
            [$this->builder()->get(), $request, $request->withMethod('POST')],
            [$this->builder()->post(), $request->withMethod('POST'), $request],
            [$this->builder()->delete(), $request->withMethod('DELETE'), $request],
            [$this->builder()->patch(), $request->withMethod('PATCH'), $request],
            [$this->builder()->put(), $request->withMethod('PUT'), $request],
            [$this->builder()->head(), $request->withMethod('HEAD'), $request],
            [$this->builder()->options(), $request->withMethod('OPTIONS'), $request]
        ];

        foreach ($cases as $case) {
            $this->checkCase(...$case);
        }
    }

    public function checkCase(Node\ContextRouteNode $builder, ServerRequestInterface $match, ServerRequestInterface $block)
    {
        $builder->callback($this->callbackResponse($response));
        $route = $builder->build();

        $prototype = new FakeResponse();
        $this->assertSame($response, $route->forward($match, $prototype));
        $this->assertSame($prototype, $route->forward($block, $prototype));
    }

    public function testMiddlewareGateway()
    {
        $builder = $this->builder();
        $builder->middleware(new FakeMiddleware('wrap'))->callback($this->callbackResponse($endpoint, 'body'));
        $route = $builder->build();

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $response  = $route->forward($request->withAttribute('middleware', 'requestPassed'), $prototype);
        $this->assertNotSame($response, $prototype);
        $this->assertSame('requestPassed: wrap body wrap', (string) $response->getBody());
    }

    public function testContainerMiddlewareGate()
    {
        $builder = $this->builder(new FakeContainer(['middleware.id' => new FakeMiddleware('wrap')]));
        $builder->containerMiddleware('middleware.id')->callback($this->callbackResponse($endpoint, 'body'));
        $route = $builder->build();

        $request   = new FakeServerRequest();
        $prototype = new FakeResponse();
        $response  = $route->forward($request->withAttribute('middleware', 'requestPassed'), $prototype);
        $this->assertNotSame($response, $prototype);
        $this->assertSame('requestPassed: wrap body wrap', (string) $response->getBody());
    }

    public function testRouteWrappedWithMultipleGates()
    {
        $builder = $this->builder();
        $builder->method('PATCH')
                ->pattern(UriPattern::fromUriString('https:/foo*'))
                ->pattern(new PathSegment('id', '[a-z]+'))
                ->callbackGate(function (ServerRequestInterface $request) { return $request->getAttribute('pass') ? $request : null; })
                ->callback($this->callbackResponse($response));
        $route = $builder->build();

        $prototype = new FakeResponse();
        $block     = new FakeServerRequest('PATCH', FakeUri::fromString('https://example.com/foo/bar'));
        $pass      = $block->withAttribute('pass', true);
        $this->assertSame($prototype, $route->forward($block, $prototype));
        $this->assertSame($response, $route->forward($pass, $prototype));
        $this->assertSame('bar', $response->fromRequest->getAttribute('id'));
    }

    public function testRouteCanBeWrappedWithCallbackInvokedWrapper()
    {
        $builder = $this->builder();
        $builder->wrapRouteCallback(function (Route $route) {
            return new Route\Gate\MethodGate('POST', $route);
        })->callback($this->callbackResponse($response));
        $route = $builder->build();

        $prototype = new FakeResponse();
        $this->assertSame($prototype, $route->forward(new FakeServerRequest('GET'), $prototype));
        $this->assertSame($response, $route->forward(new FakeServerRequest('POST'), $prototype));
    }

    public function testGatesAreEvaluatedInCorrectOrder()
    {
        $builder = $this->builder();
        $builder->pattern(new Path('foo*'))
                ->pattern(new Path('bar*'))
                ->callback($this->callbackResponse($response));
        $route = $builder->build();

        $this->assertSame('/foo/bar', (string) $route->uri(FakeUri::fromString(''), []));

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest();
        $this->assertSame($prototype, $route->forward($request->withUri(FakeUri::fromString('/bar/foo')), $prototype));
        $this->assertSame($response, $route->forward($request->withUri(FakeUri::fromString('/foo/bar')), $prototype));
    }

    public function testGatesCanWrapSplitterAndItsRoutes()
    {
        $endpoint = function (ServerRequestInterface $request) {
            return new FakeResponse('response:' . $request->getUri()->getPath());
        };
        $builder = $this->builder();
        $split   = $builder->pattern(new Path('foo*'))->responseScan();
        $split->route('routeA')->pattern(new Path('bar*'))->callback($endpoint);
        $split->route('routeB')->pattern(new Path('baz*'))->callback($endpoint);
        $route = $builder->build();

        $prototype = new FakeResponse();
        $requestA  = new FakeServerRequest('GET', FakeUri::fromString('http://example.com/foo/bar'));
        $requestB  = new FakeServerRequest('GET', FakeUri::fromString('http://example.com/foo/baz'));
        $this->assertSame('response:/foo/bar', (string) $route->forward($requestA, $prototype)->getBody());
        $this->assertSame('response:/foo/baz', (string) $route->forward($requestB, $prototype)->getBody());
    }

    public function testRouteCanBeAttachedToBuilder()
    {
        $endpoint = function ($name) {
            return function (ServerRequestInterface $request) use ($name) {
                return new FakeResponse('response' . $name . ':' . $request->getMethod());
            };
        };
        $builder = $this->builder();
        $split   = $builder->pattern(new Path('foo*'))->responseScan();
        $split->route('routeA')->pattern(new Path('bar*'))->callback($endpoint('A'));
        $split->route('routeB')->pattern(new Path('baz*'))->callback($endpoint('B'));
        $route = $builder->build();

        $builder = new Node\MethodSwitchNode();
        $builder->route('POST')->pattern(new Scheme('https'))->joinRoute($route);
        $builder->route('GET')->joinRoute($route);
        $route = $builder->build();

        $prototype = new FakeResponse();
        $requestA  = new FakeServerRequest('GET', FakeUri::fromString('http://example.com/foo/bar'));
        $requestB  = new FakeServerRequest('POST', FakeUri::fromString('http://example.com/foo/baz'));
        $this->assertSame('responseA:GET', (string) $route->forward($requestA, $prototype)->getBody());
        $this->assertSame($prototype, $route->forward($requestB, $prototype));

        $requestA = new FakeServerRequest('POST', FakeUri::fromString('https://example.com/foo/bar'));
        $requestB = new FakeServerRequest('GET', FakeUri::fromString('https://example.com/foo/baz'));
        $this->assertSame('responseA:POST', (string) $route->forward($requestA, $prototype)->getBody());
        $this->assertSame('responseB:GET', (string) $route->forward($requestB, $prototype)->getBody());
    }

    public function testBuilderCanEstablishLinkInsideStructure()
    {
        $endpoint = $this->responseRoute($response);
        $builder  = $this->builder();
        $split    = $builder->pattern(new Path('foo*'))->responseScan();
        $split->route('routeA')->pattern(new Path('bar*'))->link($endpointA)->joinRoute($endpoint);
        $split->route('routeB')->pattern(new Path('baz*'))->joinRoute($endpoint);
        $route = $builder->build();

        $builder = new Node\MethodSwitchNode();
        $builder->route('POST')->link($postRoute)->pattern(new Scheme('https'))->joinRoute($route);
        $builder->route('GET')->joinRoute($route);
        $route = $builder->build();

        $this->assertSame($endpointA, $endpoint);
        $this->assertSame($postRoute, $route->select('POST'));
    }

    public function testLinkMightBeUsedInStructureBeforeRouteIsBuilt()
    {
        $builder = new Node\MethodSwitchNode();

        $split = $builder->get()->link($link)->responseScan();
        $split->route('first')->callback($this->callbackResponse($response));
        $split->route('second')->joinRoute(new MockedRoute());

        $builder->post()->joinLink($link);

        $this->assertSame($response, $builder->build()->forward(new FakeServerRequest('POST'), self::$prototype));
    }

    public function testRoutesCanBeJoinedAfterLinkIsDefined()
    {
        $builder = new Node\RouteScanNode();
        $builder->route('second')->method('POST')->link($link)->callback($this->callbackResponse($response));
        $builder->route('first')->method('GET')->joinLink($link);
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest('DELETE'), self::$prototype));

        $builder = new Node\RouteScanNode();
        $builder->defaultRoute()->method('POST')->link($link)->callback($this->callbackResponse($response));
        $builder->route('other')->method('GET')->joinLink($link);
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest('DELETE'), self::$prototype));

        $builder = new Node\RouteScanNode();
        $builder->route('other')->method('POST')->link($link)->callback($this->callbackResponse($response));
        $builder->defaultRoute()->method('GET')->joinLink($link);
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest('DELETE'), self::$prototype));
    }

    public function testRoutesCanBeJoinedBeforeLinkIsDefined()
    {
        $builder = new Node\RouteScanNode();
        $builder->route('first')->method('GET')->joinLink($link);
        $builder->route('second')->method('POST')->link($link)->callback($this->callbackResponse($response));
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest('DELETE'), self::$prototype));

        $builder = new Node\RouteScanNode();
        $builder->route('other')->method('GET')->joinLink($link);
        $builder->defaultRoute()->method('POST')->link($link)->callback($this->callbackResponse($response));
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest('DELETE'), self::$prototype));

        $builder = new Node\RouteScanNode();
        $builder->route('other')->method('POST')->joinLink($link);
        $builder->defaultRoute()->method('GET')->link($link)->callback($this->callbackResponse($response));
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new FakeServerRequest('DELETE'), self::$prototype));
    }

    public function testRouteJoinedBackToItsOwnPath_ThrowsException()
    {
        $builder = new Node\MethodSwitchNode();
        $split   = $builder->get()->link($link)->responseScan();
        $split->route('first')->callback($this->callbackResponse($response));
        $split->route('second')->joinLink($link);
        $this->expectException(Exception\BuilderLogicException::class);
        $builder->build();
    }

    public function testRouteBuilderWithUndefinedRouterCallback_Redirect_ThrowsException()
    {
        $builder = $this->builder();
        $this->expectException(Exception\BuilderLogicException::class);
        $builder->redirect('something');
    }

    public function testRedirectEndpoint()
    {
        $router  = null;
        $builder = $this->builder(null, function () use (&$router) { return $router; });
        $path    = $builder->pathSwitch();
        $path->route('admin')->pattern(new Path('redirected'))->joinRoute(new MockedRoute());
        $path->route('redirect')->redirect('admin');

        $router   = new Router($builder->build(), new FakeUri(), new FakeResponse());
        $request  = new FakeServerRequest('GET', FakeUri::fromString('/redirect'));
        $response = $router->handle($request);
        $this->assertSame(['/admin/redirected'], $response->getHeader('Location'));
        $this->assertSame(301, $response->getStatusCode());
    }

    public function testRouteBuilderWithUndefinedContainer_Factory_ThrowsException()
    {
        $builder = $this->builder();
        $this->expectException(Exception\BuilderLogicException::class);
        $builder->factory('something');
    }

    public function testFactoryEndpoint()
    {
        $builder = $this->builder(new FakeContainer());
        $builder->factory(FakeHandlerFactory::class);
        $route = $builder->build();

        $response = $route->forward(new FakeServerRequest(), new FakeResponse());
        $this->assertSame('handler response', (string) $response->getBody());
    }

    private function builder(?ContainerInterface $container = null, ?callable $router = null): Node\ContextRouteNode
    {
        return new Node\ContextRouteNode(new NodeContext($container, $router));
    }
}
