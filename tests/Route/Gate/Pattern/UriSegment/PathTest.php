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
    public function testNotMatchingPattern_ReturnsNull()
    {
        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/foo');
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('bar/baz');
        $request = $this->request('/foo/bar/baz');
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('baz/qux');
        $request = $this->request('/foo/bar/baz/qux', 'bar/baz/qux');
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testPatternMatchingAbsolutePathWithoutContext_ReturnsRequestWithContextFragment()
    {
        $pattern = $this->pattern('foo/bar');
        $request = $this->request('/foo/bar/baz/qux');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame('baz/qux', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testPatternIsMatchedRelativelyWithContextPathWhenPresent()
    {
        $pattern = $this->pattern('bar/baz');
        $request = $this->request('/foo/bar/baz/qux', 'bar/baz/qux');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame('qux', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testEmptyPatternIsMatchedWhenContextIsEmpty()
    {
        $pattern = $this->pattern('');
        $request = $this->request('/foo/bar', 'bar');
        $this->assertNull($pattern->matchedRequest($request));

        $request = $this->request('/foo/bar', '');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    public function testEmptyPatternShouldMatchRootPath()
    {
        $pattern = $this->pattern('');
        $request = $this->request('/');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame('', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testPatternDoesNotMatchAlreadyMatchedPath()
    {
        $pattern = $this->pattern('foo/bar');
        $request = $this->request('/foo/bar');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame('', $matched->getAttribute(Route::PATH_ATTRIBUTE));
        $this->assertNull($pattern->matchedRequest($matched));
    }

    public function testUriFromRelativePatternWithRootInPrototype_ReturnsUriWithAppendedPath()
    {
        $pattern   = $this->pattern('bar/slug-string');
        $prototype = Doubles\FakeUri::fromString('/foo');
        $this->assertSame('/foo/bar/slug-string', (string) $pattern->uri($prototype, []));

        $pattern   = $this->pattern('last/segments');
        $prototype = Doubles\FakeUri::fromString('/foo/bar');
        $this->assertSame('/foo/bar/last/segments', (string) $pattern->uri($prototype, []));
    }

    public function testUriWithNoRootInPrototype_ReturnsUriWithAbsolutePath()
    {
        $pattern   = $this->pattern('bar');
        $prototype = new Doubles\FakeUri();
        $this->assertSame('/bar', $pattern->uri($prototype, [])->getPath());
    }

    private function pattern(string $path): Route\Gate\Pattern
    {
        return new Route\Gate\Pattern\UriSegment\Path($path);
    }

    private function request(string $uri, ?string $context = null): ServerRequestInterface
    {
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString($uri));
        return isset($context) ? $request->withAttribute(Route::PATH_ATTRIBUTE, $context) : $request;
    }
}
