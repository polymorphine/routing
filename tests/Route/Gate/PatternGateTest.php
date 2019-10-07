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
use Polymorphine\Routing\Route\Gate;
use Polymorphine\Routing\Tests\Doubles;


class PatternGateTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $default = $this->gate());

        $gateway = Gate\PatternGate::fromPatternString('/test/{#testId}', new Doubles\MockedRoute());
        $this->assertInstanceOf(Route::class, $gateway);

        $gateway = Gate\PatternGate::fromPatternString('//domain.com/test/foo', new Doubles\MockedRoute());
        $this->assertInstanceOf(Route::class, $gateway);
    }

    public function testNotMatchingPattern_ReturnsPrototypeInstance()
    {
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $this->gate()->forward(new Doubles\FakeServerRequest(), $prototype));
    }

    public function testMatchingPattern_ReturnsForwardedRouteResponse()
    {
        $request  = new Doubles\FakeServerRequest();
        $pattern  = new Doubles\MockedPattern('foo');
        $response = $this->gate($pattern, $route)->forward($request, new Doubles\FakeResponse());

        $this->assertSame($pattern->matchedRequest, $route->forwardedRequest);
        $this->assertNotSame($request, $pattern->matchedRequest);
        $this->assertSame($response, $route->response);
    }

    public function testUri_ReturnsUriProcessedByPatternAndFollowingRoute()
    {
        $prototype = new Doubles\FakeUri();
        $pattern   = new Doubles\MockedPattern('foo');
        $uri       = $this->gate($pattern, $route)->uri($prototype, $params = ['some' => 'params']);

        $this->assertSame($prototype, $pattern->uriPrototype);
        $this->assertSame($params, $pattern->uriParams);
        $this->assertSame($pattern->uriResult, $route->prototype);
        $this->assertSame($params, $route->params);
        $this->assertSame($uri, $route->uri);
    }

    public function testSelectMethod_ReturnsPatternGateWithSelectedSubRoute()
    {
        $selected = $this->gate($pattern, $route)->select('some.path');
        $this->assertSame('some.path', $route->path);
        $this->assertEquals($selected, new Gate\PatternGate($pattern, $route->subRoute));
    }

    public function testRoutesMethod_PassesTraceWithModifiedUriToNextRoute()
    {
        $trace   = new Map\Trace(new Map(), new Doubles\FakeUri());
        $pattern = new Doubles\MockedPattern('/foo/bar');
        $this->gate($pattern, $route)->routes($trace);
        $this->assertEquals($trace->withPattern($pattern), $route->trace);
    }

    private function gate(?Gate\Pattern &$pattern = null, ?Route &$route = null)
    {
        $route   = $route ?? new Doubles\MockedRoute(new Doubles\FakeResponse(), new Doubles\FakeUri());
        $pattern = $pattern ?? new Doubles\MockedPattern();
        return new Gate\PatternGate($pattern, $route);
    }
}
