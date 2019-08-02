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
use Polymorphine\Routing;
use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\Pattern\UriPart as Uri;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class RouteNodeTest extends TestCase
{
    use Routing\Tests\RoutingTestMethods;
    use Routing\Tests\Builder\ContextCreateMethod;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Node\RouteNode::class, $this->builder());
    }

    public function testRouteCanBeSplit()
    {
        $this->assertInstanceOf(Node\ScanSwitchNode::class, $this->builder()->responseScan());
        $this->assertInstanceOf(Node\MethodSwitchNode::class, $this->builder()->methodSwitch());
        $this->assertInstanceOf(Node\PathSwitchNode::class, $this->builder()->pathSwitch());
        $this->assertInstanceOf(Node\Resource\ResourceSwitchNode::class, $this->builder()->resource());
    }

    public function testCallbackEndpoint()
    {
        $builder = $this->builder();
        $builder->callback(function () {});
        $this->assertInstanceOf(Route\Endpoint\CallbackEndpoint::class, $builder->build());
    }

    public function testHandlerEndpoint()
    {
        $builder = $this->builder();
        $builder->handler(new Doubles\FakeRequestHandler(new Doubles\FakeResponse()));
        $this->assertInstanceOf(Route\Endpoint\HandlerEndpoint::class, $builder->build());
    }

    public function testLazyEndpoint()
    {
        $builder = $this->builder();
        $builder->lazy(function () {});
        $this->assertInstanceOf(Route\Gate\LazyRoute::class, $builder->build());
    }

    public function testSetRouteWhenAlreadyBuilt_ThrowsException()
    {
        $route = $this->builder();
        $route->callback(function () {});
        $this->expectException(Routing\Builder\Exception\BuilderLogicException::class);
        $route->pathSwitch();
    }

    public function testBuildUndefinedRoute_ThrowsException()
    {
        $builder = $this->builder();
        $this->expectException(Routing\Builder\Exception\BuilderLogicException::class);
        $builder->build();
    }

    public function testNoWildcardPathPatternForNotFullyMatchedRequestPath_ReturnsPrototype()
    {
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://example.com/foo/bar/baz'));
        $prototype = new Doubles\FakeResponse();

        $builder = $this->builder()->path('foo/bar');
        $builder->callback($this->callbackResponse($response));
        $this->assertSame($prototype, $builder->build()->forward($request, $prototype));
    }

    /**
     * @dataProvider wildcardPaths
     *
     * @param string $path
     */
    public function testWildcardPathPatternForNotFullyMatchedRequestPath_ReturnsEndpointResponse(string $path)
    {
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://example.com/foo/bar/baz'));
        $prototype = new Doubles\FakeResponse();

        $builder = $this->builder()->path($path);
        $builder->callback($this->callbackResponse($response));
        $this->assertSame($response, $builder->build()->forward($request, $prototype));
    }

    public function wildcardPaths()
    {
        return [['foo/bar*'], ['foo/bar/*'], ['foo*'], ['foo/*'], ['*']];
    }

    public function testGateWrappers()
    {
        $attrCheckCallback = function (ServerRequestInterface $request) {
            return $request->getAttribute('test') ? $request : null;
        };

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://example.com/foo'));
        $https   = Doubles\FakeUri::fromString('https://example.com/foo/bar/baz');

        $cases = [
            [$this->builder()->pattern(new Uri\Scheme('https')), $request->withUri($https), $request],
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

    public function checkCase(Node\RouteNode $builder, ServerRequestInterface $match, ServerRequestInterface $block)
    {
        $builder->path('*')->callback($this->callbackResponse($response));
        $route = $builder->build();

        $prototype = new Doubles\FakeResponse();
        $this->assertSame($response, $route->forward($match, $prototype));
        $this->assertSame($prototype, $route->forward($block, $prototype));
    }

    public function testMiddlewareGateway()
    {
        $builder = $this->builder();
        $builder->middleware(new Doubles\FakeMiddleware('wrap'))->callback($this->callbackResponse($endpoint, 'body'));
        $route = $builder->build();

        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $response  = $route->forward($request->withAttribute('middleware', 'requestPassed'), $prototype);
        $this->assertNotSame($response, $prototype);
        $this->assertSame('requestPassed: wrap body wrap', (string) $response->getBody());
    }

    public function testRouteWrappedWithMultipleGates()
    {
        $builder = $this->builder();
        $builder->method('PATCH')
                ->pattern(Route\Gate\Pattern\UriPattern::fromUriString('https:/foo'))
                ->pattern(new Uri\PathSegment('id', '[a-z]+'))
                ->callbackGate(function (ServerRequestInterface $request) { return $request->getAttribute('pass') ? $request : null; })
                ->callback($this->callbackResponse($response));
        $route = $builder->build();

        $prototype = new Doubles\FakeResponse();
        $block     = new Doubles\FakeServerRequest('PATCH', Doubles\FakeUri::fromString('https://example.com/foo/bar'));
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

        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $route->forward(new Doubles\FakeServerRequest('GET'), $prototype));
        $this->assertSame($response, $route->forward(new Doubles\FakeServerRequest('POST'), $prototype));
    }

    public function testGatesAreEvaluatedInCorrectOrder()
    {
        $builder = $this->builder();
        $builder->pattern(new Uri\Path('foo'))
                ->pattern(new Uri\Path('bar'))
                ->callback($this->callbackResponse($response));
        $route = $builder->build();

        $this->assertSame('/foo/bar', (string) $route->uri(Doubles\FakeUri::fromString(''), []));

        $prototype = new Doubles\FakeResponse();
        $request   = new Doubles\FakeServerRequest();
        $this->assertSame($prototype, $route->forward($request->withUri(Doubles\FakeUri::fromString('/bar/foo')), $prototype));
        $this->assertSame($response, $route->forward($request->withUri(Doubles\FakeUri::fromString('/foo/bar')), $prototype));
    }

    public function testGatesCanWrapSplitterAndItsRoutes()
    {
        $endpoint = function (ServerRequestInterface $request) {
            return new Doubles\FakeResponse('response:' . $request->getUri()->getPath());
        };
        $builder = $this->builder();
        $split   = $builder->pattern(new Uri\Path('foo'))->responseScan();
        $split->route('routeA')->pattern(new Uri\Path('bar'))->callback($endpoint);
        $split->route('routeB')->pattern(new Uri\Path('baz'))->callback($endpoint);
        $route = $builder->build();

        $prototype = new Doubles\FakeResponse();
        $requestA  = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://example.com/foo/bar'));
        $requestB  = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://example.com/foo/baz'));
        $this->assertSame('response:/foo/bar', (string) $route->forward($requestA, $prototype)->getBody());
        $this->assertSame('response:/foo/baz', (string) $route->forward($requestB, $prototype)->getBody());
    }

    public function testRouteCanBeAttachedToBuilderNode()
    {
        $endpoint = function ($name) {
            return function (ServerRequestInterface $request) use ($name) {
                return new Doubles\FakeResponse('response' . $name . ':' . $request->getMethod());
            };
        };
        $builder = $this->builder();
        $split   = $builder->pattern(new Uri\Path('foo'))->responseScan();
        $split->route('routeA')->pattern(new Uri\Path('bar'))->callback($endpoint('A'));
        $split->route('routeB')->pattern(new Uri\Path('baz'))->callback($endpoint('B'));
        $route = $builder->build();

        $builder = new Node\MethodSwitchNode($this->context());
        $builder->route('POST')->pattern(new Uri\Scheme('https'))->joinRoute($route);
        $builder->route('GET')->joinRoute($route);
        $route = $builder->build();

        $prototype = new Doubles\FakeResponse();
        $requestA  = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://example.com/foo/bar'));
        $requestB  = new Doubles\FakeServerRequest('POST', Doubles\FakeUri::fromString('http://example.com/foo/baz'));
        $this->assertSame('responseA:GET', (string) $route->forward($requestA, $prototype)->getBody());
        $this->assertSame($prototype, $route->forward($requestB, $prototype));

        $requestA = new Doubles\FakeServerRequest('POST', Doubles\FakeUri::fromString('https://example.com/foo/bar'));
        $requestB = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('https://example.com/foo/baz'));
        $this->assertSame('responseA:POST', (string) $route->forward($requestA, $prototype)->getBody());
        $this->assertSame('responseB:GET', (string) $route->forward($requestB, $prototype)->getBody());
    }

    public function testBuilderNodeCanEstablishLinkInsideStructure()
    {
        $endpoint = $this->responseRoute($response);
        $builder  = $this->builder();
        $split    = $builder->pattern(new Uri\Path('foo*'))->responseScan();
        $split->route('routeA')->pattern(new Uri\Path('bar*'))->link($endpointA)->joinRoute($endpoint);
        $split->route('routeB')->pattern(new Uri\Path('baz*'))->joinRoute($endpoint);
        $route = $builder->build();

        $builder = new Node\MethodSwitchNode($this->context());
        $builder->route('POST')->link($postRoute)->pattern(new Uri\Scheme('https'))->joinRoute($route);
        $builder->route('GET')->joinRoute($route);
        $route = $builder->build();

        $this->assertSame($endpointA, $endpoint);
        $this->assertSame($postRoute, $route->select('POST'));
    }

    public function testLinkMightBeUsedInStructureBeforeRouteIsBuilt()
    {
        $builder = new Node\MethodSwitchNode($this->context());

        $split = $builder->get()->link($link)->responseScan();
        $split->route('first')->callback($this->callbackResponse($response));
        $split->route('second')->joinRoute(new Doubles\MockedRoute());

        $builder->post()->joinLink($link);

        $this->assertSame($response, $builder->build()->forward(new Doubles\FakeServerRequest('POST'), self::$prototype));
    }

    public function testRoutesCanBeJoinedAfterLinkIsDefined()
    {
        $builder = new Node\ScanSwitchNode($this->context());
        $builder->route('second')->method('POST')->link($link)->callback($this->callbackResponse($response));
        $builder->route('first')->method('GET')->joinLink($link);
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new Doubles\FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('DELETE'), self::$prototype));

        $builder = new Node\ScanSwitchNode($this->context());
        $builder->defaultRoute()->method('POST')->link($link)->callback($this->callbackResponse($response));
        $builder->route('other')->method('GET')->joinLink($link);
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new Doubles\FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('DELETE'), self::$prototype));

        $builder = new Node\ScanSwitchNode($this->context());
        $builder->route('other')->method('POST')->link($link)->callback($this->callbackResponse($response));
        $builder->defaultRoute()->method('GET')->joinLink($link);
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new Doubles\FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('DELETE'), self::$prototype));
    }

    public function testRoutesCanBeJoinedBeforeLinkIsDefined()
    {
        $builder = new Node\ScanSwitchNode($this->context());
        $builder->route('first')->method('GET')->joinLink($link);
        $builder->route('second')->method('POST')->link($link)->callback($this->callbackResponse($response));
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new Doubles\FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('DELETE'), self::$prototype));

        $builder = new Node\ScanSwitchNode($this->context());
        $builder->route('other')->method('GET')->joinLink($link);
        $builder->defaultRoute()->method('POST')->link($link)->callback($this->callbackResponse($response));
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new Doubles\FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('DELETE'), self::$prototype));

        $builder = new Node\ScanSwitchNode($this->context());
        $builder->route('other')->method('POST')->joinLink($link);
        $builder->defaultRoute()->method('GET')->link($link)->callback($this->callbackResponse($response));
        $route = $builder->build();
        $this->assertSame($response, $route->forward(new Doubles\FakeServerRequest('GET'), self::$prototype));
        $this->assertSame(self::$prototype, $route->forward(new Doubles\FakeServerRequest('DELETE'), self::$prototype));
    }

    public function testRouteJoinedBackToItsOwnPath_ThrowsException()
    {
        $builder = new Node\MethodSwitchNode($this->context());
        $split   = $builder->get()->link($link)->responseScan();
        $split->route('first')->callback($this->callbackResponse($response));
        $split->route('second')->joinLink($link);
        $this->expectException(Routing\Builder\Exception\BuilderLogicException::class);
        $builder->build();
    }

    public function testRedirectEndpoint()
    {
        $router  = null;
        $builder = $this->builder(null, function () use (&$router) { return $router; });
        $path    = $builder->pathSwitch();
        $path->route('admin')->pattern(new Uri\Path('redirected'))->joinRoute(new Doubles\MockedRoute());
        $path->route('redirect')->redirect('admin');

        $router   = new Routing\Router($builder->build(), new Doubles\FakeUri(), new Doubles\FakeResponse());
        $request  = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/redirect'));
        $response = $router->handle($request);
        $this->assertSame(['/admin/redirected'], $response->getHeader('Location'));
        $this->assertSame(301, $response->getStatusCode());
    }

    public function testRedirectWithUndefinedRouterCallback_ThrowsException()
    {
        $builder = new Node\RouteNode(new Routing\Builder\Context(new Routing\Builder\MappedRoutes(null, null, null)));
        $path    = $builder->pathSwitch();
        $path->route('admin')->pattern(new Uri\Path('redirected'))->joinRoute(new Doubles\MockedRoute());
        $node = $path->route('redirect');
        $this->expectException(Routing\Builder\Exception\BuilderLogicException::class);
        $node->redirect('admin');
    }

    public function testDefaultMappedGateMethod()
    {
        $builder = $this->builder(new Doubles\FakeContainer(['middleware.id' => new Doubles\FakeMiddleware('wrap')]));
        $builder->gate('middleware.id')->callback($this->callbackResponse($endpoint, 'body'));
        $route = $builder->build();

        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();
        $response  = $route->forward($request->withAttribute('middleware', 'requestPassed'), $prototype);
        $this->assertNotSame($response, $prototype);
        $this->assertSame('requestPassed: wrap body wrap', (string) $response->getBody());
    }

    public function testMappedGateWithoutIdResolver_ThrowsException()
    {
        $builder = $this->builder();
        $this->expectException(Routing\Builder\Exception\BuilderLogicException::class);
        $builder->gate('something');
    }

    public function testDefaultMappedEndpoint()
    {
        $container = new Doubles\FakeContainer([
            'handler' => new Doubles\FakeRequestHandler(new Doubles\FakeResponse('handler response'))
        ]);

        $builder = $this->builder($container);
        $builder->endpoint(Doubles\FakeHandlerFactory::class);
        $this->assertInstanceOf(Route\Endpoint\CallbackEndpoint::class, $route = $builder->build());

        $request = (new Doubles\FakeServerRequest())->withHeader('id', 'handler');
        $this->assertInstanceOf(ResponseInterface::class, $response = $route->forward($request, new Doubles\FakeResponse()));
        $this->assertSame('handler response', (string) $response->getBody());
    }

    public function testMappedEndpointWithoutIdResolver_ThrowsException()
    {
        $builder = $this->builder();
        $this->expectException(Routing\Builder\Exception\BuilderLogicException::class);
        $builder->endpoint('something');
    }

    private function builder($container = null, ?callable $router = null): Node\RouteNode
    {
        return new Node\RouteNode($this->context($container, $router));
    }
}
