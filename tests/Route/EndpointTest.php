<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Map;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;


class EndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route\Endpoint::class, new Doubles\DummyEndpoint());
    }

    public function testSelectCall_ThrowsException()
    {
        $route = new Doubles\DummyEndpoint();
        $this->expectException(Exception\SwitchCallException::class);
        $route->select('foo');
    }

    public function testUriCall_ReturnsPrototype()
    {
        $route = new Doubles\DummyEndpoint();
        $uri   = new Doubles\FakeUri();
        $this->assertSame($uri, $route->uri($uri, []));
    }

    public function testOptionsMethod_ReturnsAllowedMethodsHeader()
    {
        $route   = new Doubles\DummyEndpoint();
        $methods = ['GET', 'POST', 'DELETE'];

        $request = (new Doubles\FakeServerRequest('OPTIONS'))->withAttribute(Route::METHODS_ATTRIBUTE, $methods);
        $this->assertSame([implode(', ', $methods)], $route->forward($request, new Doubles\FakeResponse())->getHeader('Allow'));
    }

    public function testForwardedRequestWithFullyProcessedPathOrWildcardAttribute_ReturnsResponse()
    {
        $route     = new Doubles\DummyEndpoint();
        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();

        $request->uri = Doubles\FakeUri::fromString('http://no.path/');
        $this->assertNotSame($prototype, $route->forward($request, $prototype));

        $request->uri = Doubles\FakeUri::fromString('http://with.path/some/path');
        $this->assertNotSame($prototype, $route->forward($request->withAttribute(Route::PATH_ATTRIBUTE, []), $prototype));
    }

    public function testForwardedRequestWithUnprocessedPathAndNoWildcardAttribute_ReturnsPrototype()
    {
        $route     = new Doubles\DummyEndpoint();
        $request   = new Doubles\FakeServerRequest();
        $prototype = new Doubles\FakeResponse();

        $request->uri = Doubles\FakeUri::fromString('http://with.path/some/path');
        $this->assertSame($prototype, $route->forward($request, $prototype));

        $request->uri = Doubles\FakeUri::fromString('http://path.from.attribute');
        $this->assertSame($prototype, $route->forward($request->withAttribute(Route::PATH_ATTRIBUTE, ['some', 'path']), $prototype));
    }

    public function testRoutesMethod_AddsTracedPathToRoutingMap()
    {
        $path  = new Map\Path('some.routing.path', '*', 'https://example.com/foo/bar');
        $trace = new Map\Trace($map = new Map(), Doubles\FakeUri::fromString($path->uri));
        $trace = $trace->nextHop($path->name);

        $route = new Doubles\DummyEndpoint();
        $route->routes($trace);

        $this->assertEquals([$path], $map->paths());
    }
}
