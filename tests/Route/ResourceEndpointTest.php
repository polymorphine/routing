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
use Polymorphine\Routing\Route\ResourceEndpoint;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Routing\Exception\UriParamsException;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;


class ResourceEndpointTest extends TestCase
{
    private static $notFound;

    public static function setUpBeforeClass()
    {
        self::$notFound = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $resource = $this->resource('/some/path'));
        $this->assertInstanceOf(ResourceEndpoint::class, $resource);
    }

    /**
     * @dataProvider notMatchingRequests
     *
     * @param ServerRequestInterface $request
     * @param Route                  $resource
     * @param string                 $message
     */
    public function testNotMatchingRequest_ReturnsNotFoundResponseInstance(
        ServerRequestInterface $request,
        Route $resource,
        string $message
    ) {
        $this->assertSame(self::$notFound, $resource->forward($request, self::$notFound), $message);
    }

    public function notMatchingRequests()
    {
        return [
            [$this->request('/foo/bar', 'POST'), $this->resource('/foo/bar', ['GET', 'INDEX']), 'method should not be allowed'],
            [$this->request('/foo/bar', 'DOIT'), $this->resource('/foo/bar', ['DOIT', 'INDEX']), 'methods other than POST or GET should require resource id'],
            [$this->request('/foo/something', 'GET'), $this->resource('/foo/bar'), 'path should not match'],
            [$this->request('/foo/bar/123', 'POST'), $this->resource('/foo/bar'), 'cannot post to id'],
            [$this->request('/foo/bar/a8b3ccf0', 'GET'), $this->resource('/foo/bar', ['GET']), 'invalid id format should not match'],
            [$this->request('/foo/bar', 'GET'), $this->resource('/foo/bar', ['GET']), 'resource list should be defined by INDEX pseudo-method'],
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
    public function testMatchingRequest_ReturnsEndpointDefinedResponse(
        ServerRequestInterface $request,
        Route $resource
    ) {
        $this->assertNotSame(self::$notFound, $resource->forward($request, self::$notFound));
    }

    public function matchingRequests()
    {
        return [
            [$this->request('/foo/bar', 'POST'), $this->resource('/foo/bar', ['POST', 'PUT'])],
            [$this->request('/foo/bar', 'GET'), $this->resource('/foo/bar', ['INDEX', 'POST'])],
            [$this->request('/foo/bar/7645', 'GET'), $this->resource('/foo/bar', ['GET'])],
            [$this->request('/foo/bar/7645/slug-name', 'PUT'), $this->resource('/foo/bar', ['PUT'])],
            [$this->request('/foo/bar/7645/some-string-300', 'ANYTHING'), $this->resource('/foo/bar', ['ANYTHING'])],
            [$this->request('/foo/bar/baz'), $this->resource('bar/baz')],
            [$this->request('/foo/bar/baz/600'), $this->resource('bar/baz')],
            [$this->request('/some/path/500/slug-string-1000'), $this->resource('some/path')]
        ];
    }

    public function testMatchingIdRequestIsForwardedWithIdAttribute()
    {
        $request  = $this->request('/foo/345');
        $response = $this->resource('/foo')->forward($request, self::$notFound);
        $this->assertSame(['id' => '345'], $response->fromRequest->getAttributes());

        $request  = $this->request('/foo/666/slug/3000', 'PATCH');
        $response = $this->resource('/foo', ['PATCH'])->forward($request, self::$notFound);
        $this->assertSame(['id' => '666'], $response->fromRequest->getAttributes());

        $request  = $this->request('/foo/bar/baz/554', 'PATCH');
        $response = $this->resource('baz')->forward($request, self::$notFound);
        $this->assertSame(['id' => '554'], $response->fromRequest->getAttributes());

        $request  = $this->request('/some/path/500/slug-string-1000', 'PATCH');
        $response = $this->resource('some/path')->forward($request, self::$notFound);
        $this->assertSame(['id' => '500'], $response->fromRequest->getAttributes());
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
        $this->expectException(UriParamsException::class);
        $this->resource('/path/to/resource')->uri(new FakeUri(), ['id' => '08ab']);
    }

    public function testUriPrototypeWithDefinedPath_ThrowsException()
    {
        $this->expectException(UnreachableEndpointException::class);
        $this->resource('/foo/bar')->uri(FakeUri::fromString('/other/path'), []);
    }

    public function testUriForRelativePathWithoutPrototypePath_throwsException()
    {
        $resource = $this->resource('bar/baz');
        $this->expectException(UnreachableEndpointException::class);
        $resource->uri(FakeUri::fromString('http://example.com'), []);
    }

    public function testUriForRelativePath_ReturnsUriWithPathAppendedToPrototype()
    {
        $resource = $this->resource('bar/baz');
        $uri      = $resource->uri(FakeUri::fromString('http://example.com/'), ['id' => '3456']);
        $this->assertSame('http://example.com/bar/baz/3456', (string) $uri);
    }

    private function resource(string $path, array $methods = ['INDEX', 'POST', 'GET', 'PUT', 'PATCH', 'DELETE'])
    {
        $handlers = [];
        foreach ($methods as $method) {
            $handlers[$method] = $this->dummyCallback();
        }

        return new ResourceEndpoint($path, $handlers);
    }

    private function dummyCallback()
    {
        return function ($request) {
            $response = new FakeResponse();

            $response->fromRequest = $request;

            return $response;
        };
    }

    private function request($path, $method = null)
    {
        $request = new FakeServerRequest();

        $request->method = $method ?? 'GET';
        $request->uri    = FakeUri::fromString($path);

        return $request;
    }
}
