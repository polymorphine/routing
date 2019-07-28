<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Gate\Pattern\UriSegment;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;


class PathTest extends TestCase
{
    public function testNotMatchingAbsolutePath_ReturnsNull()
    {
        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/');
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/foo');
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/foo/something/bar');
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testMatchingAbsolutePath_ReturnsRequestWithContextFragment()
    {
        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/foo/bar/baz/and/anything');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame('baz/and/anything', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testAbsolutePathIsMatchedWithRequestPathRegardlessOfContext()
    {
        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/other/path/foo', 'foo/bar');
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/foo/bar/baz/qux', 'any/path');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame('baz/qux', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testNotMatchingRelativePath_ReturnsNull()
    {
        $pattern = $this->pattern('bar/baz');
        $request = $this->request('/foo/bar/baz');
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('baz/qux');
        $request = $this->request('/foo/bar/baz/qux', 'bar/baz/qux');
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testRelativePathMatchingAbsolutePathWithoutContext_ReturnsRequestWithContextFragment()
    {
        $pattern = $this->pattern('foo/bar');
        $request = $this->request('/foo/bar/baz/qux');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame('baz/qux', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testRelativePathIsMatchedWithContextPathWhenPresent()
    {
        $pattern = $this->pattern('bar/baz');
        $request = $this->request('/any/path/foo', 'bar/baz/qux');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame('qux', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testEmptyRelativePathIsMatchedWhenContextIsEmpty()
    {
        $pattern = $this->pattern('');
        $request = $this->request('/foo/bar')->withAttribute(Route::PATH_ATTRIBUTE, 'bar');
        $this->assertNull($pattern->matchedRequest($request));

        $request = $request->withAttribute(Route::PATH_ATTRIBUTE, '');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    public function testEmptyPathShouldMatchRootPath()
    {
        $pattern = $this->pattern('');
        $request = $this->request('/');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame('', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testRelativePathPatternDoesNotMatchBeyondRequestPath()
    {
        $pattern = $this->pattern('foo/bar');
        $request = $this->request('/foo/bar');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertNull($pattern->matchedRequest($matched));
    }

    public function testUriFromRelativePathWithRootInPrototype_ReturnsUriWithAppendedPath()
    {
        $pattern   = $this->pattern('bar/slug-string');
        $prototype = Doubles\FakeUri::fromString('/foo');
        $this->assertSame('/foo/bar/slug-string', (string) $pattern->uri($prototype, []));

        $pattern   = $this->pattern('last/segments');
        $prototype = Doubles\FakeUri::fromString('/foo/bar');
        $this->assertSame('/foo/bar/last/segments', (string) $pattern->uri($prototype, []));
    }

    public function testUriFromRelativePathWithNoRootInPrototype_ReturnsUriWithAbsolutePath()
    {
        $pattern   = $this->pattern('bar');
        $prototype = new Doubles\FakeUri();
        $this->assertSame('/bar', $pattern->uri($prototype, [])->getPath());
    }

    private function pattern(string $path)
    {
        return new Route\Gate\Pattern\UriSegment\Path($path);
    }

    private function request(string $uri, string $context = null)
    {
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString($uri));
        return $context ? $request->withAttribute(Route::PATH_ATTRIBUTE, $context) : $request;
    }
}
