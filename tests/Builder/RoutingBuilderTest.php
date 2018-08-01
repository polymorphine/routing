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
use Polymorphine\Routing\Builder\RouteBuilder;
use Polymorphine\Routing\Builder\ContainerRouteBuilder;
use Polymorphine\Routing\Builder\SwitchBuilder;
use Polymorphine\Routing\Builder\MethodSwitchBuilder;
use Polymorphine\Routing\Builder\ResponseScanSwitchBuilder;
use Polymorphine\Routing\Builder\PathSegmentSwitchBuilder;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Route\Pattern\UriPattern;
use Polymorphine\Routing\Route\Pattern\UriSegment\Scheme;
use Polymorphine\Routing\Route\Pattern\UriSegment\Path;
use Polymorphine\Routing\Route\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Exception\BuilderCallException;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Tests\Doubles\FakeContainer;
use Polymorphine\Routing\Tests\Doubles\FakeHandlerFactory;
use Polymorphine\Routing\Tests\Doubles\FakeMiddleware;
use Polymorphine\Routing\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;


class RoutingBuilderTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(SwitchBuilder::class, new ResponseScanSwitchBuilder());
        $this->assertInstanceOf(SwitchBuilder::class, new PathSegmentSwitchBuilder());
        $this->assertInstanceOf(RouteBuilder::class, new RouteBuilder());
    }

    public function testBuildSwitchBuilder_ReturnsConcreteRouteSplitter()
    {
        $this->assertInstanceOf(Route\Splitter\ResponseScanSwitch::class, (new ResponseScanSwitchBuilder())->build());
        $this->assertInstanceOf(Route\Splitter\PathSegmentSwitch::class, (new PathSegmentSwitchBuilder())->build());
    }

    public function testRoutesCanBeAddedToSwitchBuilder()
    {
        $switch = new ResponseScanSwitchBuilder();
        $switch->route('allMatch')->callback(function () { return new FakeResponse('matched'); });
        $switch->route('secondMatch')->callback(function () {});

        $route = $switch->build();
        $this->assertSame('matched', (string) $route->forward(new FakeServerRequest(), new FakeResponse())->getBody());

        $switch = new PathSegmentSwitchBuilder();
        $switch->route('foo')->callback(function () { return new FakeResponse('foo matched'); });
        $switch->route('bar')->lazy(function () { return new CallbackEndpoint(function () { return new FakeResponse('bar matched'); }); });
        $route = $switch->build();

        $requestFoo = new FakeServerRequest('GET', FakeUri::fromString('/foo'));
        $requestBar = new FakeServerRequest('GET', FakeUri::fromString('bar'));
        $this->assertSame('foo matched', (string) $route->forward($requestFoo, new FakeResponse())->getBody());
        $this->assertSame('bar matched', (string) $route->forward($requestBar, new FakeResponse())->getBody());
    }

    public function testUnnamedRoutesCanBeAddedToResponseScanSwitch()
    {
        $response = new FakeResponse();
        $endpoint = function () use ($response) { return $response; };
        $attribute = function ($attribute) {
            return function (ServerRequestInterface $request) use ($attribute) {
                return $request->getAttribute('test') === $attribute ? $request : null;
            };
        };
        $switch = new ResponseScanSwitchBuilder();
        $switch->route()->callbackGate($attribute('A'))->callback($endpoint);
        $switch->route()->callbackGate($attribute('B'))->callback($endpoint);
        $route = $switch->build();

        $prototype = new FakeResponse();
        $request   = new FakeServerRequest();
        $this->assertSame($prototype, $route->forward($request, $prototype));
        $this->assertSame($response, $route->forward($request->withAttribute('test', 'A'), $prototype));
        $this->assertSame($response, $route->forward($request->withAttribute('test', 'B'), $prototype));
    }

    public function testHandlerEndpoint()
    {
        $response = new FakeResponse('response');
        $builder  = new RouteBuilder();
        $builder->handler(new FakeRequestHandler($response));
        $this->assertSame($response, $builder->build()->forward(new FakeServerRequest(), new FakeResponse()));
    }

    public function testMiddlewareGate()
    {
        $builder = new RouteBuilder();
        $builder->middleware(new FakeMiddleware('wrap'))->callback(function () { return new FakeResponse('response'); });
        $route = $builder->build();

        $request   = (new FakeServerRequest())->withAttribute('middleware', 'requestPassed');
        $prototype = new FakeResponse();
        $this->assertSame('requestPassed: wrap response wrap', (string) $route->forward($request, $prototype)->getBody());
    }

    public function testAddingRouteWithAlreadyDefinedName_ThrowsException()
    {
        $switch = new ResponseScanSwitchBuilder();
        $switch->route('exists')->callback(function () { return new FakeResponse('matched'); });
        $this->expectException(InvalidArgumentException::class);
        $switch->route('exists');
    }

    public function testRouteCanBeSplit()
    {
        $this->assertInstanceOf(ResponseScanSwitchBuilder::class, (new RouteBuilder())->responseScan());
        $this->assertInstanceOf(MethodSwitchBuilder::class, (new RouteBuilder())->methodSwitch());
        $this->assertInstanceOf(PathSegmentSwitchBuilder::class, (new RouteBuilder())->pathSwitch());
    }

    public function testSetRouteWhenAlreadyBuilt_ThrowsException()
    {
        $route = new RouteBuilder();
        $route->callback(function () {});
        $this->expectException(BuilderCallException::class);
        $route->pathSwitch();
    }

    public function testBuildUndefinedRoute_ThrowsException()
    {
        $builder = new RouteBuilder();
        $this->expectException(BuilderCallException::class);
        $builder->build();
    }

    public function testGateWrappers()
    {
        $attrCheckCallback = function (ServerRequestInterface $request) {
            return $request->getAttribute('test') ? $request : null;
        };

        $request = new FakeServerRequest('GET', FakeUri::fromString('http://example.com/foo'));
        $https   = FakeUri::fromString('https:/example.com/foo');

        $cases = [
            [(new RouteBuilder())->pattern(new Scheme('https')), $request->withUri($https), $request],
            [(new RouteBuilder())->callbackGate($attrCheckCallback), $request->withAttribute('test', true), $request],
            [(new RouteBuilder())->method('PATCH'), $request->withMethod('PATCH'), $request],
            [(new RouteBuilder())->get(), $request, $request->withMethod('POST')],
            [(new RouteBuilder())->post(), $request->withMethod('POST'), $request],
            [(new RouteBuilder())->delete(), $request->withMethod('DELETE'), $request],
            [(new RouteBuilder())->patch(), $request->withMethod('PATCH'), $request],
            [(new RouteBuilder())->put(), $request->withMethod('PUT'), $request],
            [(new RouteBuilder())->head(), $request->withMethod('HEAD'), $request],
            [(new RouteBuilder())->options(), $request->withMethod('OPTIONS'), $request]
        ];

        foreach ($cases as $case) {
            $this->checkCase(...$case);
        }
    }

    public function checkCase(RouteBuilder $builder, ServerRequestInterface $match, ServerRequestInterface $block)
    {
        $blocked  = new FakeResponse('blocked');
        $passed   = new FakeResponse('matched');
        $endpoint = function () use ($passed) { return $passed; };

        $builder->callback($endpoint);
        $route = $builder->build();
        $this->assertSame($passed, $route->forward($match, $blocked));
        $this->assertSame($blocked, $route->forward($block, $blocked));
    }

    public function testRouteWrappedWithMultipleGates()
    {
        $builder = new RouteBuilder();
        $builder->method('PATCH')
                ->pattern(UriPattern::fromUriString('https:/foo*'))
                ->pattern(new PathSegment('id', '[a-z]+'))
                ->callbackGate(function (ServerRequestInterface $request) { return $request->getAttribute('pass') ? $request : null; })
                ->callback(function (ServerRequestInterface $request) { return new FakeResponse('passed:' . $request->getAttribute('id')); });
        $route     = $builder->build();
        $prototype = new FakeResponse();

        $requestA = new FakeServerRequest('PATCH', FakeUri::fromString('https://example.com/foo/bar'));
        $requestB = $requestA->withAttribute('pass', true);
        $this->assertSame($prototype, $route->forward($requestA, $prototype));
        $this->assertSame('passed:bar', (string) $route->forward($requestB, $prototype)->getBody());
    }

    public function testGatesAreEvaluatedInCorrectOrder()
    {
        $builder = new RouteBuilder();
        $builder->pattern(new Path('foo*'))
                ->pattern(new Path('bar*'))
                ->callback(function () { return new FakeResponse('passed'); });
        $route = $builder->build();

        $this->assertSame('/foo/bar', (string) $route->uri(FakeUri::fromString(''), []));

        $prototype = new FakeResponse();
        $requestA  = new FakeServerRequest('GET', FakeUri::fromString('https://example.com/bar/foo'));
        $requestB  = new FakeServerRequest('GET', FakeUri::fromString('https://example.com/foo/bar'));
        $this->assertSame($prototype, $route->forward($requestA, $prototype));
        $this->assertNotSame($prototype, $route->forward($requestB, $prototype));
    }

    public function testGatesCanWrapSplitterAndItsRoutes()
    {
        $endpoint = function (ServerRequestInterface $request) {
            return new FakeResponse('response:' . $request->getUri()->getPath());
        };
        $builder = new RouteBuilder();
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
        $builder = new RouteBuilder();
        $split   = $builder->pattern(new Path('foo*'))->responseScan();
        $split->route('routeA')->pattern(new Path('bar*'))->callback($endpoint('A'));
        $split->route('routeB')->pattern(new Path('baz*'))->callback($endpoint('B'));
        $route = $builder->build();

        $builder = new MethodSwitchBuilder();
        $builder->route('POST')->pattern(new Scheme('https'))->join($route);
        $builder->route('GET')->join($route);
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
        $endpoint = new Route\Endpoint\CallbackEndpoint(function (ServerRequestInterface $request) {
            return new FakeResponse('response:' . $request->getMethod());
        });
        $builder = new RouteBuilder();
        $split   = $builder->pattern(new Path('foo*'))->responseScan();
        $split->route('routeA')->pattern(new Path('bar*'))->link($endpointA)->join($endpoint);
        $split->route('routeB')->pattern(new Path('baz*'))->join($endpoint);
        $route = $builder->build();

        $builder = new MethodSwitchBuilder();
        $builder->route('POST')->link($postRoute)->pattern(new Route\Pattern\UriSegment\Scheme('https'))->join($route);
        $builder->route('GET')->join($route);
        $route = $builder->build();

        $this->assertSame($endpointA, $endpoint);
        $this->assertSame($postRoute, $route->select('POST'));
    }

    public function testRootRouteCanBeSetForPathSwitch()
    {
        $endpoint = new CallbackEndpoint(function (ServerRequestInterface $request) {
            return new FakeResponse('passed:' . $request->getUri()->getPath());
        });

        $builder = (new RouteBuilder());
        $path    = $builder->pathSwitch();
        $path->route('bar')->join($endpoint);

        $path->root($endpoint);

        $route     = $builder->build();
        $prototype = new FakeResponse();
        $request   = new FakeServerRequest('GET', FakeUri::fromString('//example.com/'));
        $this->assertSame('passed:/', (string) $route->forward($request, $prototype)->getBody());
    }

    public function testAddingMethodSplitterWithUnknownHttpMethod_ThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $builder = new MethodSwitchBuilder();
        $builder->route('INDEX')->callback(function () { return new FakeResponse(); });
    }

    public function testRouteBuilderRedirectMethod_ThrowsException()
    {
        $builder = new RouteBuilder();
        $this->expectException(BuilderCallException::class);
        $builder->redirect('something');
    }

    public function testRedirectEndpoint()
    {
        $container = new FakeContainer();
        $routerId  = 'ROUTER';
        $builder   = new ContainerRouteBuilder($container, $routerId);
        $path      = $builder->pathSwitch();

        $path->route('admin')->pattern(new Path('redirected'))->join(new MockedRoute());
        $path->route('redirect')->redirect('admin');

        $router   = $container->records[$routerId]   = new Router($builder->build(), new FakeUri(), new FakeResponse());
        $response = $router->handle(new FakeServerRequest('GET', FakeUri::fromString('/redirect')));
        $this->assertSame('/admin/redirected', $response->getHeader('Location'));
        $this->assertSame(301, $response->getStatusCode());
    }

    public function testRouteBuilderFactoryMethod_ThrowsException()
    {
        $builder = new RouteBuilder();
        $this->expectException(BuilderCallException::class);
        $builder->factory('something');
    }

    public function testFactoryEndpoint()
    {
        $container = new FakeContainer();
        $routerId  = 'ROUTER';
        $builder   = new ContainerRouteBuilder($container, $routerId);

        $builder->factory(FakeHandlerFactory::class);

        $router   = $container->records[$routerId]   = new Router($builder->build(), new FakeUri(), new FakeResponse());
        $response = $router->handle(new FakeServerRequest());
        $this->assertSame('handler response', (string) $response->getBody());
    }
}
