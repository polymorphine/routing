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
use Polymorphine\Routing\Route\Gate\Pattern;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;


class QueryTest extends TestCase
{
    use Pattern\UriTemplatePlaceholder;

    public function testInstantiation()
    {
        $pattern = $this->pattern('foo=bar');
        $this->assertInstanceOf(Pattern::class, $pattern);
        $this->assertInstanceOf(Pattern\UriPart\Query::class, $pattern);
    }

    /**
     * @dataProvider matchingPatterns
     *
     * @param string $pattern
     * @param string $uri
     */
    public function testMatchedRequestWithMatchingQueryParams_ReturnsRequest(string $pattern, string $uri)
    {
        $pattern = $this->pattern($pattern);
        $request = $this->request($uri);
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    public function matchingPatterns()
    {
        return [
            ['foo', 'http://example.com/path?foo'],
            ['foo', 'http://example.com/path?foo='],
            ['foo', 'http://example.com/path?foo=bar'],
            ['foo=', 'http://example.com/path?foo='],
            ['foo=bar', 'http://example.com/path?other=param&foo=bar'],
            ['foo=bar', 'http://example.com/path?foo=bar&other=param'],
            ['foo=bar&fizz=buzz', 'http://example.com/path?fizz=buzz&foo=bar&other=param'],
            ['foo=bar&fizz', 'http://example.com/path?fizz=buzz&foo=bar&other=param'],
            ['foo=bar&fizz', 'http://example.com/path?fizz&foo=bar&other=param']
        ];
    }

    /**
     * @dataProvider notMatchingPatterns
     *
     * @param string $pattern
     * @param string $uri
     */
    public function testMatchedRequestWithNotMatchingQueryParams_ReturnsNull(string $pattern, string $uri)
    {
        $pattern = $this->pattern($pattern);
        $request = $this->request($uri);
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function notMatchingPatterns()
    {
        return [
            ['foo', 'http://example.com/path?bar=foo'],
            ['foo=', 'http://example.com/path?foo'],
            ['foo=', 'http://example.com/path?foo=bar'],
            ['foo=bar', 'http://example.com/path?other=param&foo=fizz'],
            ['foo=bar&fizz=buzz', 'http://example.com/path?fizz=buzz&other=param'],
            ['foo=bar&fizz=', 'http://example.com/path?fizz=buzz&foo=bar&other=param']
        ];
    }

    /**
     * @dataProvider uriBuilds
     *
     * @param string $pattern
     * @param string $prototype
     * @param string $uri
     */
    public function testUri_ReturnsUriWithAppendedQueryParams(string $pattern, string $prototype, string $uri)
    {
        $pattern   = $this->pattern($pattern);
        $prototype = $this->uri('http://example.com/path?' . $prototype);
        $this->assertSame('http://example.com/path?' . $uri, (string) $pattern->uri($prototype, []));
    }

    public function uriBuilds()
    {
        return [
            ['foo', '', 'foo'],
            ['foo=', 'other=param', 'other=param&foo='],
            ['foo=bar', 'other=param', 'other=param&foo=bar'],
            ['foo=bar', 'foo&other=param', 'foo=bar&other=param'],
            ['foo=bar&fizz=buzz', 'fizz&other=param', 'fizz=buzz&other=param&foo=bar']
        ];
    }

    /**
     * @dataProvider prototypeConflicts
     *
     * @param string $pattern
     * @param string $prototype
     */
    public function testUriForNotMatchingPrototypeParam_ThrowsException(string $pattern, string $prototype)
    {
        $pattern   = $this->pattern($pattern);
        $prototype = $this->uri('http://example.com/path?' . $prototype);
        $this->expectException(Exception\InvalidUriPrototypeException::class);
        $pattern->uri($prototype, []);
    }

    public function prototypeConflicts()
    {
        return [
            ['foo=', 'foo=1'],
            ['foo=bar', 'foo=baz'],
            ['foo=bar', 'other=param&foo=baz'],
            ['foo=bar&fizz=buzz', 'fizz=buzz&foo=baz&other=param']
        ];
    }

    public function testUriMethodParamsSetQueryParametersForUndefinedPatternValues()
    {
        $pattern   = $this->pattern('foo&bar&baz=qux');
        $prototype = $this->uri('');

        $uri = $pattern->uri($prototype, ['foo' => 'fizz', 'baz' => 'ignored']);
        $this->assertSame('foo=fizz&bar&baz=qux', $uri->getQuery());

        $uri = $pattern->uri($uri, ['foo' => 'ignored', 'bar' => 'buzz']);
        $this->assertSame('foo=fizz&bar=buzz&baz=qux', $uri->getQuery());
    }

    public function testUriTemplate_ReturnsUriWithQueryPlaceholders()
    {
        $pattern  = $this->pattern('foo&bar=&baz=qux');
        $uri      = Doubles\FakeUri::fromString('/fizz/buzz');
        $expected = $uri->withQuery('foo=' . $this->placeholder('*') . '&bar=&baz=qux');
        $this->assertEquals($expected, $pattern->templateUri($uri));
    }

    /**
     * @dataProvider prototypeConflicts
     *
     * @param string $pattern
     * @param string $prototype
     */
    public function testTemplateUriForNotMatchingPrototypeParam_ThrowsException(string $pattern, string $prototype)
    {
        $pattern   = $this->pattern($pattern);
        $prototype = $this->uri('http://example.com/path?' . $prototype);
        $this->expectException(Exception\InvalidUriPrototypeException::class);
        $pattern->templateUri($prototype);
    }

    private function pattern(string $query): Pattern
    {
        return Pattern\UriPart\Query::fromQueryString($query);
    }

    private function request(string $uri)
    {
        $request = new Doubles\FakeServerRequest();
        $request->uri = $this->uri($uri);
        return $request;
    }

    private function uri(string $uri)
    {
        return Doubles\FakeUri::fromString($uri);
    }
}
