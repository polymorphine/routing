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
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;


class PathTest extends TestCase
{
    public function testNotMatchingAbsolutePattern_ReturnsNull()
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

    public function testMatchingAbsolutePattern_ReturnsRequestWithContextFragment()
    {
        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/foo/bar/baz/and/anything');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame('baz/and/anything', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testAbsolutePatternThatReachesCurrentContext_IsMatched()
    {
        $pattern = $this->pattern('/foo');
        $request = $this->request('/foo/bar/baz/qux', 'bar/baz/qux');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame('bar/baz/qux', $matched->getAttribute(Route::PATH_ATTRIBUTE));

        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/foo/bar/baz/qux', 'bar/baz/qux');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame('baz/qux', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testAbsolutePatternThatDoesNotReachCurrentContext_IsNotMatched()
    {
        $pattern = $this->pattern('/foo');
        $request = $this->request('/foo/bar/baz/qux', 'baz/qux');
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('/foo/bar');
        $request = $this->request('/foo/bar/baz/qux', 'qux');
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testNotMatchingRelativePattern_ReturnsNull()
    {
        $pattern = $this->pattern('bar/baz');
        $request = $this->request('/foo/bar/baz');
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('baz/qux');
        $request = $this->request('/foo/bar/baz/qux', 'bar/baz/qux');
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testRelativePatternMatchingAbsolutePathWithoutContext_ReturnsRequestWithContextFragment()
    {
        $pattern = $this->pattern('foo/bar');
        $request = $this->request('/foo/bar/baz/qux');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame('baz/qux', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testRelativePatternIsMatchedWithContextPathWhenPresent()
    {
        $pattern = $this->pattern('bar/baz');
        $request = $this->request('/any/path/foo', 'bar/baz/qux');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame('qux', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testEmptyRelativePatternIsMatchedWhenContextIsEmpty()
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

    public function testRelativePatternDoesNotMatchAlreadyMatchedPath()
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

    public function testUriFromRelativePatternWithNoRootInPrototype_ReturnsUriWithAbsolutePath()
    {
        $pattern   = $this->pattern('bar');
        $prototype = new Doubles\FakeUri();
        $this->assertSame('/bar', $pattern->uri($prototype, [])->getPath());
    }

    /**
     * @dataProvider validContextMutation
     *
     * @param string      $pattern
     * @param string      $request
     * @param null|string $context
     * @param string      $newContext
     */
    public function testContextChangeWithAbsolutePathPattern(string $pattern, string $request, ?string $context, string $newContext)
    {
        $pattern = $this->pattern($pattern);
        $request = $this->request($request, $context);
        $this->assertSame($newContext, $pattern->matchedRequest($request)->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function validContextMutation()
    {
        return [
            ['/foo', '/foo/bar/baz/qux', null, 'bar/baz/qux'],
            ['/foo', '/foo/bar/baz/qux', 'bar/baz/qux', 'bar/baz/qux'],
            ['/foo/bar', '/foo/bar/baz/qux', 'bar/baz/qux', 'baz/qux'],
            ['/foo/bar', '/foo/bar/baz/qux', 'baz/qux', 'baz/qux']
        ];
    }

    public function testUriForAbsolutePatternThatDoesntCoverBuiltPrototype_ThrowsException()
    {
        $pattern   = $this->pattern('/foo/bar');
        $prototype = Doubles\FakeUri::fromString('/foo/bar/baz');
        $this->expectException(Exception\UnreachableEndpointException::class);
        $pattern->uri($prototype, []);
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
