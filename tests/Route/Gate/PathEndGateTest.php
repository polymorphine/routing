<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Gate;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\PathEndGate;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ResponseInterface;


class PathEndGateTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(PathEndGate::class, new PathEndGate(new Doubles\MockedRoute()));
    }

    public function testEmptyPathIsPassedForward()
    {
        $gate    = $this->gateResponse($response);
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/'));
        $this->assertSame($response, $gate->forward($request, new Doubles\FakeResponse()));
    }

    public function testRequestWithPathIsBlocked()
    {
        $gate      = $this->gateResponse($response);
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/foo/bar'));
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $gate->forward($request, $prototype));
    }

    public function testEmptyRelativePathIsPassedForward()
    {
        $gate    = $this->gateResponse($response);
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/foo/bar'));
        $request = $request->withAttribute(Route::PATH_ATTRIBUTE, '');
        $this->assertSame($response, $gate->forward($request, new Doubles\FakeResponse()));
    }

    public function testNotEmptyRelativePathIsBlocked()
    {
        $gate      = $this->gateResponse($response);
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/foo/bar/baz'));
        $request   = $request->withAttribute(Route::PATH_ATTRIBUTE, 'bar/baz');
        $prototype = new Doubles\FakeResponse();
        $this->assertSame($prototype, $gate->forward($request, $prototype));
    }

    public function testSelectPreservesPathGate()
    {
        $route = new Doubles\MockedRoute();
        $gate  = new PathEndGate($route);
        $this->assertInstanceOf(PathEndGate::class, $gate->select('some.path'));
        $this->assertSame('some.path', $route->path);
    }

    public function testUriIsBuiltWithSubsequentRoute()
    {
        $gate      = $this->gateUri('?query=string');
        $prototype = Doubles\FakeUri::fromString('/some/path');
        $this->assertSame('/some/path?query=string', (string) $gate->uri($prototype, []));
    }

    public function testMatchingPathFromSubsequentRouteBuildsUri()
    {
        $gate      = $this->gateUri('/some/path?query=string');
        $prototype = Doubles\FakeUri::fromString('/some/path');
        $this->assertSame('/some/path?query=string', (string) $gate->uri($prototype, []));
    }

    public function testPathExtendedBySubsequentRoute_ThrowsException()
    {
        $gate      = $this->gateUri('/some/path/extended');
        $prototype = Doubles\FakeUri::fromString('/some/path');
        $this->expectException(Exception\UnreachableEndpointException::class);
        $gate->uri($prototype, []);
    }

    public function testPathChangedBySubsequentRoute_ThrowsException()
    {
        $gate      = $this->gateUri('/other/path');
        $prototype = Doubles\FakeUri::fromString('/some/path');
        $this->expectException(Exception\UnreachableEndpointException::class);
        $gate->uri($prototype, []);
    }

    private function gateResponse(?ResponseInterface &$response)
    {
        $response = new Doubles\FakeResponse();
        return new PathEndGate(new Doubles\MockedRoute($response));
    }

    private function gateUri(string $uri = '/')
    {
        return new PathEndGate(new Doubles\MockedRoute(null, Doubles\FakeUri::fromString($uri)));
    }
}
