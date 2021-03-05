<?php declare(strict_types=1);

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
use Polymorphine\Routing\Route\Gate\Pattern;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;


class PathSegmentTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Pattern::class, $this->pattern('name'));
    }

    public function testMatchingRequest_ReturnsRequestWithNewContext()
    {
        $pattern = $this->pattern('foo');
        $request = $this->request('foo/bar/baz');
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame(['bar', 'baz'], $matched->getAttribute(Route::PATH_ATTRIBUTE));

        $pattern = $this->pattern('bar');
        $request = $this->request('foo/bar', ['bar']);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame([], $matched->getAttribute(Route::PATH_ATTRIBUTE));

        $pattern = $this->pattern('bar');
        $request = $this->request('foo/bar/baz', ['bar', 'baz']);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched = $pattern->matchedRequest($request));
        $this->assertSame(['baz'], $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testNotMatchingRequest_ReturnsNull()
    {
        $pattern = $this->pattern('foo');
        $request = $this->request('foo/bar/baz', ['bar', 'baz']);
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('foo');
        $request = $this->request('foo/bar/baz', []);
        $this->assertNull($pattern->matchedRequest($request));

        $pattern = $this->pattern('bar');
        $request = $this->request('foo/bar/baz', ['baz']);
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testUri_ReturnsPrototypeWithExpandedPath()
    {
        $pattern   = $this->pattern('foo');
        $prototype = Doubles\FakeUri::fromString('http://example.com');
        $this->assertSame('http://example.com/foo', (string) $pattern->uri($prototype, []));

        $pattern   = $this->pattern('bar');
        $prototype = Doubles\FakeUri::fromString('http://example.com/foo');
        $this->assertSame('http://example.com/foo/bar', (string) $pattern->uri($prototype, []));
    }

    public function testUriTemplate_ReturnsUriWithExpandedPath()
    {
        $pattern   = $this->pattern('foo');
        $prototype = Doubles\FakeUri::fromString('http://example.com');
        $this->assertSame('http://example.com/foo', (string) $pattern->templateUri($prototype));

        $pattern   = $this->pattern('bar');
        $prototype = Doubles\FakeUri::fromString('http://example.com/foo');
        $this->assertSame('http://example.com/foo/bar', (string) $pattern->templateUri($prototype));
    }

    private function pattern(string $name): Pattern\UriPart\PathSegment
    {
        return new Pattern\UriPart\PathSegment($name);
    }

    private function request(string $uri, array $context = null): ServerRequestInterface
    {
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString($uri));
        return isset($context) ? $request->withAttribute(Route::PATH_ATTRIBUTE, $context) : $request;
    }
}
