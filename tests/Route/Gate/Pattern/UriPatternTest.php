<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Gate\Pattern;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\Pattern\UriPattern;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;


class UriPatternTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(UriPattern::class, $this->pattern('http:/some/path&query=foo'));
    }

    public function testInstantiationWithInvalidUriString_ThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pattern('http:///example.com');
    }

    /**
     * @dataProvider matchingPatterns
     *
     * @param $patternString
     * @param $uriString
     */
    public function testMatchAgainstDefinedUriParts($patternString, $uriString)
    {
        $request = $this->request($uriString);
        $this->assertInstanceOf(ServerRequestInterface::class, $this->pattern($patternString)->matchedRequest($request));
    }

    public function matchingPatterns()
    {
        return [
            ['https:', 'https://example.com'],
            ['//www.example.com', 'http://www.example.com/some/path'],
            ['http:/some/path', 'http://whatever.com/some/path?query=part&ignored=values'],
            ['?query=foo&bar=baz', 'http://example.com/some/path?query=foo&bar=baz'],
            ['//example.com:9002', 'https://example.com:9002/foo/path'],
            ['?query=bar&foo', '?query=bar&foo=anything'],
            ['?query=bar&foo=', '?foo=&query=bar']
        ];
    }

    /**
     * @dataProvider notMatchingPatterns
     *
     * @param $patternString
     * @param $uriString
     */
    public function testNotMatchAgainstDefinedUriParts($patternString, $uriString)
    {
        $request = $this->request($uriString);
        $this->assertNull($this->pattern($patternString)->matchedRequest($request));
    }

    public function notMatchingPatterns()
    {
        return [
            ['https:', 'http://example.com'],
            ['//www.example.com', 'http://example.com/some/path'],
            ['http:/some/path', 'http://whatever.com/some/other/path?query=part&ignored=values'],
            ['?query=foo&bar=baz', 'http://example.com/some/path?query=foo&bar=qux'],
            ['//example.com:8080', '//example.com:9001'],
            ['//example.com:8080', '//example.com'],
            ['?query=bar&foo', '?query=bar'],
            ['?query=bar&foo=', '?foo=emptyRequired&query=bar'],
            ['/some/path?query=string', '/some/path']
        ];
    }

    /**
     * @dataProvider patterns
     *
     * @param $patternString
     * @param $uriString
     * @param $expected
     */
    public function testUriIsReturnedWithDefinedUriParts($patternString, $uriString, $expected)
    {
        $prototype = FakeUri::fromString($uriString);
        $pattern   = $this->pattern($patternString);
        $this->assertSame($expected, (string) $pattern->uri($prototype, []));
    }

    public function patterns()
    {
        return [
            ['', 'https://example.com/some/path?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['https:', '//example.com/some/path?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['//example.com', 'https:/some/path?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['/some/path', 'https://example.com?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['?query=params&foo=bar', 'https://example.com/some/path', 'https://example.com/some/path?query=params&foo=bar'],
            ['https://example.com?query=params&foo=bar', '//example.com/some/path', 'https://example.com/some/path?query=params&foo=bar'],
            ['//example.com/some/path', 'https:?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['//user:pass@example.com?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar', 'https://user:pass@example.com/some/path?query=params&foo=bar'],
            ['//example.com:9001', 'http://example.com/foo/bar', 'http://example.com:9001/foo/bar'],
            ['?foo=&some', 'foo/bar?some=value', 'foo/bar?some=value&foo='],
            ['?foo=&some=value', 'foo/bar?foo&some', 'foo/bar?foo=&some=value']
        ];
    }

    /**
     * @dataProvider prototypeConflict
     *
     * @param $patternString
     * @param $uriString
     */
    public function testUriOverwritingPrototypeSegment_ThrowsException($patternString, $uriString)
    {
        $this->expectException(UnreachableEndpointException::class);
        $this->pattern($patternString)->uri(FakeUri::fromString($uriString), []);
    }

    public function prototypeConflict()
    {
        return [
            ['http:', 'https://example.com'],
            ['https://www.example.com', 'https://example.com'],
            ['/foo/bar/baz', '/foo//baz'],
            ['//user:pass@example.com', '//www.example.com'],
            ['?foo=bar&some=value', '?foo=bar&some=otherValue'],
            ['?foo=&some=value', '?foo=something&some=value']
        ];
    }

    public function testUriMatchingPrototypeSegment_ReturnsUriWithMissingPartAppended()
    {
        $pattern   = $this->pattern('/foo/bar/baz');
        $prototype = FakeUri::fromString('/foo/bar');
        $this->assertSame('/foo/bar/baz', (string) $pattern->uri($prototype, []));

        $pattern   = $this->pattern('/foo/bar?fizz=buzz&other=param');
        $prototype = FakeUri::fromString('/foo?fizz=buzz');
        $this->assertSame('/foo/bar?fizz=buzz&other=param', (string) $pattern->uri($prototype, []));
    }

    public function testRelativePathIsMatched()
    {
        $pattern = $this->pattern('bar');
        $matched = $pattern->matchedRequest($this->request('/foo/bar')->withAttribute(Route::PATH_ATTRIBUTE, 'bar'));
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame('', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testEmptyRelativePathIsMatched()
    {
        $pattern = $this->pattern('');
        $request = $this->request('/foo/bar')->withAttribute(Route::PATH_ATTRIBUTE, 'bar');
        $this->assertNull($pattern->matchedRequest($request));

        $request = $request->withAttribute(Route::PATH_ATTRIBUTE, '');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    public function testAbsolutePathWithAsteriskMatchesPathFragment()
    {
        $pattern = $this->pattern('/foo/bar*');
        $matched = $pattern->matchedRequest($this->request('/foo/bar/baz/and/anything'));
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame('baz/and/anything', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testPathWithAsteriskAndQueryCanBeMatched()
    {
        $pattern = $this->pattern('foo/bar*?query=foo');
        $request = $this->request('//example.com/foo/bar/baz?param=bar&query=foo');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    public function testPathWithAsteriskAndQueryCanBeMatchedInRelativeContext()
    {
        $pattern = $this->pattern('foo/bar*?query=foo');
        $request = $this->request('//example.com/fizz/foo/bar/baz?param=bar&query=foo')
                        ->withAttribute(Route::PATH_ATTRIBUTE, 'foo/bar');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    public function testRelativePathPatternDoesNotMatchBeyondRequestPath()
    {
        $pattern = $this->pattern('foo/bar*');
        $matched = $pattern->matchedRequest($this->request('/foo/bar'));
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertNull($pattern->matchedRequest($matched));
    }

    public function testUriFromRelativePathWithRootInPrototype_ReturnsUriWithAppendedPath()
    {
        $pattern   = $this->pattern('bar/slug-string');
        $prototype = FakeUri::fromString('/foo');
        $this->assertSame('/foo/bar/slug-string', (string) $pattern->uri($prototype, []));

        $pattern   = $this->pattern('last/segments?query=string');
        $prototype = FakeUri::fromString('/foo/bar');
        $this->assertSame('/foo/bar/last/segments?query=string', (string) $pattern->uri($prototype, []));
    }

    public function testUriFromRelativePathWithNoRootInPrototype_ReturnsUriWithAbsolutePath()
    {
        $pattern   = $this->pattern('bar');
        $prototype = new FakeUri();
        $this->assertSame('/bar', $pattern->uri($prototype, [])->getPath());
    }

    public function testHostStartingWithAsteriskMatchesDomainAndSubdomainRequests()
    {
        $pattern = $this->pattern('//*example.com');
        $request = $this->request('http://subdomain.example.com/foo');
        $this->assertSame($request, $pattern->matchedRequest($request));

        $request = $this->request('https://example.com/foo');
        $this->assertSame($request, $pattern->matchedRequest($request));
    }

    private function pattern(string $uri)
    {
        return UriPattern::fromUriString($uri);
    }

    private function request(string $uri)
    {
        return new FakeServerRequest('GET', FakeUri::fromString($uri));
    }
}