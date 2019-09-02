<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Gate\Pattern\UriPart;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Tests\Doubles;


class PathWildcardTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route\Gate\Pattern::class, new Route\Gate\Pattern\UriPart\PathWildcard());
    }

    public function testMatchedRequestMethod_ReturnsRequestWithCapturedAndClearedRelativePath()
    {
        $pattern = new Route\Gate\Pattern\UriPart\PathWildcard();

        $request = $pattern->matchedRequest($this->request('/foo/bar/baz'));
        $this->assertSame('foo/bar/baz', $request->getAttribute(Route::WILDCARD_ATTRIBUTE));
        $this->assertSame([], $request->getAttribute(Route::PATH_ATTRIBUTE));

        $request = $pattern->matchedRequest($this->request('/foo/bar/baz', 'bar/baz'));
        $this->assertSame('bar/baz', $request->getAttribute(Route::WILDCARD_ATTRIBUTE));
        $this->assertSame([], $request->getAttribute(Route::PATH_ATTRIBUTE));

        $request = $pattern->matchedRequest($this->request('/'));
        $this->assertSame('', $request->getAttribute(Route::WILDCARD_ATTRIBUTE));
        $this->assertSame([], $request->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testUriMethod_ReturnsPrototype()
    {
        $pattern   = new Route\Gate\Pattern\UriPart\PathWildcard();
        $prototype = Doubles\FakeUri::fromString('http://example.com/foo/bar?query=baz');

        $this->assertSame($prototype, $pattern->uri($prototype, ['anything' => 'xxx']));
    }

    public function testUriTemplate_ReturnsUriWithAsteriskEndingPath()
    {
        $pattern = new Route\Gate\Pattern\UriPart\PathWildcard();
        $uri     = Doubles\FakeUri::fromString('http://example.com/foo/bar?query=baz');

        $this->assertSame('http://example.com/foo/bar((:/*:))?query=baz', (string) $pattern->templateUri($uri));
    }

    private function request(string $uri, string $relativePath = null)
    {
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString($uri));
        return $relativePath ? $request->withAttribute(Route::PATH_ATTRIBUTE, explode('/', $relativePath)) : $request;
    }
}
