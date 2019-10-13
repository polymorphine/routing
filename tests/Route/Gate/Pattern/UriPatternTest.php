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
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;


class UriPatternTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route\Gate\Pattern\UriPattern::class, $this->pattern('http:/some/path&query=foo'));
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
            ['/foo/bar/{#baz.Qux}', 'https://example.com:9002/foo/bar/123/another?some=query'],
            ['foo/{$bar}/baz', 'https://example.com:9002/foo/bar-part/baz/another?some=query'],
            ['?query=bar&foo', '?query=bar&foo=anything'],
            ['?query=bar&foo', '?foo&query=bar'],
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
            ['/foo/bar/{#baz}', 'https://example.com:9002/foo/bar/123zzz/another?some=query'],
            ['foo/{$bar}/baz', 'https://example.com:9002/foo/bar_part/baz/another?some=query'],
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
        $prototype = Doubles\FakeUri::fromString($uriString);
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

    public function testUriMatchingPrototypeSegment_ReturnsUriWithMissingPartAppended()
    {
        $pattern   = $this->pattern('bar/baz');
        $prototype = Doubles\FakeUri::fromString('/foo/bar');
        $this->assertSame('/foo/bar/bar/baz', (string) $pattern->uri($prototype, []));

        $pattern   = $this->pattern('bar/baz?fizz=buzz&other=param');
        $prototype = Doubles\FakeUri::fromString('/foo?fizz=buzz');
        $this->assertSame('/foo/bar/baz?fizz=buzz&other=param', (string) $pattern->uri($prototype, []));
    }

    public function testPathFragmentAndQueryCanBeMatched()
    {
        $pattern = $this->pattern('foo/bar?query=foo');
        $request = $this->request('//example.com/foo/bar/baz?param=bar&query=foo');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    public function testPathFragmentAndQueryCanBeMatchedInRelativeContext()
    {
        $pattern = $this->pattern('foo/bar?query=foo');
        $request = $this->request('//example.com/fizz/foo/bar/baz?param=bar&query=foo')
                        ->withAttribute(Route::PATH_ATTRIBUTE, ['foo', 'bar', 'baz']);
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    public function testUriFromRelativePathWithQueryAndRootInPrototype_ReturnsUriWithAppendedPath()
    {
        $pattern   = $this->pattern('last/segments?query=string');
        $prototype = Doubles\FakeUri::fromString('/foo/bar');
        $this->assertSame('/foo/bar/last/segments?query=string', (string) $pattern->uri($prototype, []));
    }

    public function testHostStartingWithAsteriskMatchesDomainAndSubdomainRequests()
    {
        $pattern = $this->pattern('//*example.com');
        $request = $this->request('http://subdomain.example.com/foo');
        $this->assertSame($request, $pattern->matchedRequest($request));

        $request = $this->request('https://example.com/foo');
        $this->assertSame($request, $pattern->matchedRequest($request));
    }

    public function testQueryPatternsArePartiallyMatched()
    {
        $patternA = $this->pattern('?foo');
        $patternB = $this->pattern('?bar=buzz');
        $request  = $this->request('http://subdomain.example.com/path?bar=buzz&foo=fizz');
        $this->assertSame($request, $patternA->matchedRequest($request));
        $this->assertSame($request, $patternB->matchedRequest($request));
    }

    public function testTemplateUri_ReturnsAppendsUriTemplatesFromAllPatterns()
    {
        $pattern = $this->pattern('https://example.com:5000');
        $uri     = Doubles\FakeUri::fromString('/foo/bar');

        $this->assertSame('https://example.com:5000/foo/bar', (string) $pattern->templateUri($uri));
    }

    /**
     * @dataProvider prototypeConflict
     *
     * @param $patternString
     * @param $uriString
     */
    public function testTemplateUriOverwritingPrototypeSegment_ThrowsException($patternString, $uriString)
    {
        $pattern = $this->pattern($patternString);
        $this->expectException(Exception\InvalidUriPrototypeException::class);
        $pattern->templateUri(Doubles\FakeUri::fromString($uriString));
    }

    public function prototypeConflict()
    {
        return [
            ['http:', 'https://example.com'],
            ['https://www.example.com', 'https://example.com'],
            ['//user:pass@example.com', '//user@example.com'],
            ['?foo=bar&some=value', '?foo=bar&some=otherValue'],
            ['?foo=&some=value', '?foo=something&some=value']
        ];
    }

    public function testEmptySegmentConstraintForNotEmptyTemplatePrototype_ThrowsException()
    {
        $pattern   = new Route\Gate\Pattern\UriPart\Port('');
        $prototype = Doubles\FakeUri::fromString('//example.com:123');
        $this->expectException(Exception\InvalidUriPrototypeException::class);
        $pattern->templateUri($prototype);
    }

    private function pattern(string $uri)
    {
        return Route\Gate\Pattern\UriPattern::fromUriString($uri);
    }

    private function request(string $uri)
    {
        return new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString($uri));
    }
}
