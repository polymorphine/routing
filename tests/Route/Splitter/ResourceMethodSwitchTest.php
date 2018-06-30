<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Splitter;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Splitter\ResourceMethodSwitch;
use Polymorphine\Routing\Exception\SwitchCallException;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Routing\Exception\InvalidUriParamsException;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;


class ResourceMethodSwitchTest extends TestCase
{
    private static $prototype;

    public static function setUpBeforeClass()
    {
        self::$prototype = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $resource = $this->resource('/some/path'));
        $this->assertInstanceOf(ResourceMethodSwitch::class, $resource);
    }

    /**
     * @dataProvider notMatchingRequests
     *
     * @param ServerRequestInterface $request
     * @param Route                  $resource
     * @param string                 $message
     */
    public function testForwardNotMatchingRequest_ReturnsPrototypeInstance(
        ServerRequestInterface $request,
        Route $resource,
        string $message
    ) {
        $this->assertSame(self::$prototype, $resource->forward($request, self::$prototype), $message);
    }

    public function notMatchingRequests()
    {
        return [
            [$this->request('/foo/bar', 'POST'), $this->resource('/foo/bar', null, ['GET', 'INDEX']), 'method should not be allowed'],
            [$this->request('/foo/bar', 'DO-IT'), $this->resource('/foo/bar', null, ['DO-IT', 'INDEX']), 'methods other than POST or GET require resource id'],
            [$this->request('/foo/something', 'GET'), $this->resource('/foo/bar'), 'path should not match'],
            [$this->request('/foo/bar/123', 'POST'), $this->resource('/foo/bar'), 'cannot post to id'],
            [$this->request('/foo/bar/a8b3ccf0', 'GET'), $this->resource('/foo/bar', null, ['GET']), 'invalid id format should not match'],
            [$this->request('/foo/bar', 'GET'), $this->resource('/foo/bar', null, ['GET']), 'resource list should be defined by INDEX pseudo-method'],
            [$this->request('/foo/bar'), $this->resource('baz'), 'relative resource path is not substring of request path'],
            [$this->request('/some/path/foo'), $this->resource('some/path'), 'no resource id'],
            [$this->request('/some/path/666'), $this->resource('me/path'), 'not a path segment']
        ];
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param ServerRequestInterface $request
     * @param Route                  $resource
     */
    public function testMatchingRequest_ReturnsEndpointDefinedResponse(ServerRequestInterface $request, Route $resource)
    {
        $this->assertNotSame(self::$prototype, $resource->forward($request, self::$prototype));
    }

    public function matchingRequests()
    {
        return [
            [$this->request('/foo/bar', 'POST'), $this->resource('/foo/bar', null, ['POST', 'PUT'])],
            [$this->request('/foo/bar', 'GET'), $this->resource('/foo/bar', null, ['INDEX', 'POST'])],
            [$this->request('/foo/bar/7645', 'GET'), $this->resource('/foo/bar', null, ['GET'])],
            [$this->request('/foo/bar/7645/slug-name', 'PUT'), $this->resource('/foo/bar', null, ['PUT'])],
            [$this->request('/foo/bar/7645/some-string-300', 'ANYTHING'), $this->resource('/foo/bar', null, ['ANYTHING'])],
            [$this->request('/foo/bar/baz')->withAttribute(Route::PATH_ATTRIBUTE, 'bar/baz'), $this->resource('bar/baz')],
            [$this->request('/foo/bar/baz/600')->withAttribute(Route::PATH_ATTRIBUTE, 'bar/baz/600'), $this->resource('bar/baz')],
            [$this->request('/some/path/500/slug-string-1000'), $this->resource('some/path')]
        ];
    }

    public function testMatchingIdRequestIsForwardedWithIdAndRemainingPathAttributes()
    {
        $route = MockedRoute::response('endpoint');

        $request = $this->request('/foo/345');
        $this->resource('/foo', $route)->forward($request, self::$prototype);
        $this->assertSame('345', $route->forwardedRequest->getAttribute('id'));
        $this->assertSame('', $route->forwardedRequest->getAttribute(Route::PATH_ATTRIBUTE));

        $request = $this->request('/foo/666/slug/3000', 'PATCH');
        $this->resource('/foo', $route, ['PATCH'])->forward($request, self::$prototype);
        $this->assertSame('666', $route->forwardedRequest->getAttribute('id'));
        $this->assertSame('slug/3000', $route->forwardedRequest->getAttribute(Route::PATH_ATTRIBUTE));

        $request = $this->request('/foo/bar/baz/554', 'PATCH')->withAttribute(Route::PATH_ATTRIBUTE, 'baz/554');
        $this->resource('baz', $route, ['PATCH'])->forward($request, self::$prototype);
        $this->assertSame('554', $route->forwardedRequest->getAttribute('id'));
        $this->assertSame('', $route->forwardedRequest->getAttribute(Route::PATH_ATTRIBUTE));

        $request = $this->request('/some/path/500/slug-string-1000', 'PATCH');
        $this->resource('some/path', $route, ['PATCH'])->forward($request, self::$prototype);
        $this->assertSame('500', $route->forwardedRequest->getAttribute('id'));
        $this->assertSame('slug-string-1000', $route->forwardedRequest->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testUriMethod_ReturnsUriWithPath()
    {
        $resource = $this->resource('/foo/bar');
        $this->assertSame('/foo/bar', (string) $resource->uri(new FakeUri(), []));

        $uri = FakeUri::fromString('http://example.com:9000?query=string');
        $this->assertSame('http://example.com:9000/foo/bar?query=string', (string) $resource->uri($uri, []));
    }

    public function testUriMethodWithIdParam_ReturnsUriWithIdPath()
    {
        $resource = $this->resource('/some/path');
        $this->assertSame('/some/path/239', (string) $resource->uri(new FakeUri(), [239]));

        $uri = FakeUri::fromString('http://example.com:9000?query=string');
        $this->assertSame('http://example.com:9000/some/path/300?query=string', (string) $resource->uri($uri, ['id' => 300]));
    }

    public function testUriWithInvalidIdParam_ThrowsException()
    {
        $this->expectException(InvalidUriParamsException::class);
        $this->resource('/path/to/resource')->uri(new FakeUri(), ['id' => '08ab']);
    }

    public function testUriPrototypeWithDefinedPath_ThrowsException()
    {
        $this->expectException(UnreachableEndpointException::class);
        $this->resource('/foo/bar')->uri(FakeUri::fromString('/other/path'), []);
    }

    public function testUriForRelativePathWithoutPrototypePath_ReturnsUriWithAbsolutePath()
    {
        $resource = $this->resource('bar/baz');
        $uri      = $resource->uri(FakeUri::fromString('http://example.com'), []);
        $this->assertSame('http://example.com/bar/baz', (string) $uri);
    }

    public function testUriForRelativePath_ReturnsUriWithPathAppendedToPrototype()
    {
        $resource = $this->resource('bar/baz');
        $uri      = $resource->uri(FakeUri::fromString('http://example.com/'), ['id' => '3456']);
        $this->assertSame('http://example.com/bar/baz/3456', (string) $uri);
    }

    public function testSelectCallNotMatchingMethod_ThrowsException()
    {
        $this->expectException(SwitchCallException::class);
        $this->resource('/user/posts')->select('user');
    }

    public function testSelectWithDefinedMethod_ReturnsSingleMethodResourceSwitchInstance()
    {
        $resource = $this->resource('/user/profile')->select('PUT');
        $this->assertInstanceOf(ResourceMethodSwitch::class, $resource);
        $this->assertEquals($this->resource('/user/profile', null, ['PUT']), $resource);
    }

    public function testUriForMultipleMethodSwitch_ReturnsUriForGETorINDEXMethod()
    {
        $resource = $this->resourceWithDistinctUris('posts');

        $this->assertSame('//index.example.com/posts', (string) $resource->uri(new FakeUri(), []));
        $this->assertSame('//get.example.com/posts/123', (string) $resource->uri(new FakeUri(), ['id' => 123]));
    }

    public function testUriForSingleMethodInstance_ReturnsUriForSpecificMethodRoute()
    {
        $resource = $this->resource('/user/profile', MockedRoute::withUri('//put.example.com?with=query'), ['PUT']);
        $this->assertSame('//put.example.com/user/profile/123?with=query', (string) $resource->uri(new FakeUri(), ['id' => 123]));
    }

    public function testUriFromSelectedMethod_ReturnsUriForThisMethod()
    {
        $resource = $this->resourceWithDistinctUris('/posts')->select('PATCH');
        $this->assertSame('//patch.example.com/posts/45', (string) $resource->uri(new FakeUri(), ['id' => 45]));
    }

    public function testUriFromMultipleMethodSwitchWithUndefinedGETMethod_ThrowsException()
    {
        $resource = $this->resourceWithDistinctUris('/resources', ['GET']);
        $this->expectException(SwitchCallException::class);
        $resource->uri(new FakeUri(), ['id' => 666]);
    }

    public function testUriFromMultipleMethodSwitchWithUndefinedINDEXMethod_ThrowsException()
    {
        $resource = $this->resourceWithDistinctUris('/resources', ['INDEX']);
        $this->expectException(SwitchCallException::class);
        $resource->uri(new FakeUri(), []);
    }

    private function resourceWithDistinctUris(string $path, array $remove = []): Route
    {
        $routes = [
            'INDEX'  => MockedRoute::withUri('//index.example.com'),
            'POST'   => MockedRoute::withUri('//post.example.com'),
            'GET'    => MockedRoute::withUri('//get.example.com'),
            'PATCH'  => MockedRoute::withUri('//patch.example.com'),
            'PUT'    => MockedRoute::withUri('//put.example.com'),
            'DELETE' => MockedRoute::withUri('//delete.example.com')
        ];

        foreach ($remove as $method) {
            unset($routes[$method]);
        }

        return new ResourceMethodSwitch($path, $routes);
    }

    private function resource(string $path, ?Route $route = null, array $methods = ['INDEX', 'POST', 'GET', 'PUT', 'PATCH', 'DELETE'])
    {
        $routes = [];
        foreach ($methods as $method) {
            $routes[$method] = $route ?? MockedRoute::response($method);
        }

        return new ResourceMethodSwitch($path, $routes);
    }

    private function request($path, $method = null)
    {
        $request         = new FakeServerRequest();
        $request->method = $method ?? 'GET';
        $request->uri    = FakeUri::fromString($path);
        return $request;
    }
}
