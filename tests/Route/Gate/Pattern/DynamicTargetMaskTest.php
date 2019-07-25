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
use Polymorphine\Routing\Route\Gate\Pattern;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;


class DynamicTargetMaskTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Pattern\DynamicTargetMask::class, $this->pattern());
        $this->assertInstanceOf(Pattern::class, $this->pattern());
    }

    public function testNotMatchingRequest_ReturnsNull()
    {
        $pattern = $this->pattern('/page/{#no}');
        $this->assertNull($pattern->matchedRequest($this->request('/page/next')));
        $this->assertNull($pattern->matchedRequest($this->request('/page')));
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param $pattern
     * @param $uri
     */
    public function testMatchingRequest_ReturnsRequestBack($pattern, $uri)
    {
        $pattern = $this->pattern($pattern);
        $request = $this->request($uri);
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param $pattern
     * @param $uri
     * @param $attr
     */
    public function testMatchingRequest_ReturnsRequestWithMatchedAttributes($pattern, $uri, $attr)
    {
        $pattern = $this->pattern($pattern);
        $request = $this->request($uri);
        $this->assertSame($attr, $pattern->matchedRequest($request)->getAttributes());
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param $pattern
     * @param $uri
     * @param $attr
     */
    public function testUriReplacesProvidedValues($pattern, $uri, $attr)
    {
        $pattern = $this->pattern($pattern);
        $this->assertSame($uri, (string) $pattern->uri(new Doubles\FakeUri(), array_values($attr)));
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param $pattern
     * @param $uri
     * @param $attr
     */
    public function testUriNamedParamsCanBePassedOutOfOrder($pattern, $uri, $attr)
    {
        $pattern = $this->pattern($pattern);
        $this->assertSame($uri, (string) $pattern->uri(new Doubles\FakeUri(), array_reverse($attr, true)));
    }

    public function matchingRequests()
    {
        return [
            'no-params'  => ['/path/only', '/path/only', []],
            'id'         => ['/page/{#no}', '/page/4', ['no' => '4']],
            'id+slug'    => ['/page/{#no}/{$title}', '/page/576/foo-bar-45', ['no' => '576', 'title' => 'foo-bar-45']],
            'literal-id' => ['/foo-{@name}', '/foo-bar5000', ['name' => 'bar5000']],
            'query'      => ['/path/and?user={#id}', '/path/and?user=938', ['id' => '938']],
            'query+path' => ['/path/user/{#id}?foo={$bar}', '/path/user/938?foo=bar-BAZ', ['id' => '938', 'bar' => 'bar-BAZ']]
        ];
    }

    public function testUriInsufficientParams_ThrowsException()
    {
        $pattern = $this->pattern('/some-{#number}/{$slug}');
        $this->expectException(Exception\InvalidUriParamsException::class);
        $pattern->uri(new Doubles\FakeUri(), [22]);
    }

    public function testUriInvalidTypeParams_ThrowsException()
    {
        $pattern = $this->pattern('/user/{#countryId}');
        $this->expectException(Exception\InvalidUriParamsException::class);
        $pattern->uri(new Doubles\FakeUri(), ['Poland']);
    }

    public function testQueryStringMatchIgnoresParamOrder()
    {
        $pattern = $this->pattern('/path/and?user={#id}&foo={$bar}');
        $request = $this->request('/path/and?foo=bar-BAZ&user=938');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame(['id' => '938', 'bar' => 'bar-BAZ'], $matched->getAttributes());
    }

    public function testQueryStringIsIgnoredWhenNotSpecifiedInRoute()
    {
        $pattern = $this->pattern('/path/{@directory}');
        $request = $this->request('/path/something?foo=bar-BAZ&user=938');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame(['directory' => 'something'], $matched->getAttributes());
    }

    public function testNotSpecifiedQueryParamsAreIgnored()
    {
        $pattern = $this->pattern('/path/only?name={$slug}&user=938');
        $request = $this->request('/path/only?foo=bar-BAZ&user=938&name=shudd3r');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame(['slug' => 'shudd3r'], $matched->getAttributes());
    }

    public function testMissingQueryParamWontMatchRequest()
    {
        $pattern = $this->pattern('/path/only?name={$slug}&user=938');
        $request = $this->request('/path/only?foo=bar-BAZ&name=shudd3r');
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testPatternQueryKeyWithoutValue()
    {
        $pattern = $this->pattern('/foo/{@bar}?name={$name}&fizz');

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example'));
        $this->assertNull($request);

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example&fizz'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);

        $request = $pattern->matchedRequest($this->request('/foo/bar?fizz=anything&name=slug-example'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame(['bar' => 'bar', 'name' => 'slug-example'], $request->getAttributes());

        $uri = $pattern->uri(Doubles\FakeUri::fromString('http://example.com'), ['something', 'slug-string']);
        $this->assertSame('http://example.com/foo/something?name=slug-string&fizz', (string) $uri);
    }

    public function testEmptyQueryParamValueIsRequiredAsSpecified()
    {
        $pattern = $this->pattern('/foo/{@bar}?name={$name}&fizz=');

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example&fizz=something'));
        $this->assertNull($request);

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example&fizz'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example&fizz='));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
    }

    public function testUnusedUriParamsAreIgnored()
    {
        $pattern = $this->pattern('/foo/{@bar}?name={$name}&fizz=buzz');

        $params = ['something', 'slug-string', 'unused-param'];
        $uri    = $pattern->uri(Doubles\FakeUri::fromString('https://www.example.com'), $params);
        $this->assertSame('https://www.example.com/foo/something?name=slug-string&fizz=buzz', (string) $uri);

        $params = ['unused' => 'something', 'name' => 'slug-string', 'bar' => 'name'];
        $uri    = $pattern->uri(Doubles\FakeUri::fromString('https://www.example.com'), $params);
        $this->assertSame('https://www.example.com/foo/name?name=slug-string&fizz=buzz', (string) $uri);
    }

    /**
     * @dataProvider prototypeConflict
     *
     * @param $pattern
     * @param $uri
     */
    public function testUriOverwritingPrototypeSegment_ThrowsException($pattern, $uri)
    {
        $pattern = $this->pattern($pattern);
        $this->expectException(Exception\UnreachableEndpointException::class);
        $pattern->uri(Doubles\FakeUri::fromString($uri), ['id' => 1500]);
    }

    public function prototypeConflict()
    {
        return [
            'different absolute paths' => ['/foo/bar/baz', '/foo/baz'],
            'different query param fizz' => ['/foo/bar?anything&fizz=buzz', '?anything=foo&fizz=baz'],
            'different query param fizz (defined empty)' => ['/foo/{#id}?some=query&fizz=buzz', '?some=query&fizz=']
        ];
    }

    /**
     * @dataProvider prototypeSegmentMatch
     *
     * @param $pattern
     * @param $proto
     * @param $params
     * @param $expected
     */
    public function testUriMatchingPrototypeSegment_ReturnsUriWithMissingPartsAppended($pattern, $proto, $params, $expected)
    {
        $pattern = $this->pattern($pattern);
        $this->assertSame($expected, (string) $pattern->uri(Doubles\FakeUri::fromString($proto), $params));
    }

    public function prototypeSegmentMatch()
    {
        return [
            ['{#id}', '/user', [1500], '/user/1500'],
            ['foo/{#id}?some=foo', '?other=bar', [673], '/foo/673?other=bar&some=foo'],
            ['/foo/bar/baz', '/foo/bar', [], '/foo/bar/baz'],
            ['/foo/{#id}?some=query&fizz=buzz', '?some=query&fizz', [123], '/foo/123?some=query&fizz=buzz'],
            ['/foo?fizz', '?fizz=foo&buzz=something', [], '/foo?fizz=foo&buzz=something']
        ];
    }

    public function testMatchWithProvidedPattern()
    {
        $pattern = new Pattern\DynamicTargetMask('/some/path/{hex}', ['hex' => '[A-F0-9]+']);
        $request = $this->request('/some/path/D6E8A9F6');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
        $this->assertSame(['hex' => 'D6E8A9F6'], $pattern->matchedRequest($request)->getAttributes());

        $request = $this->request('/some/path/d6e8a9f6');
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testUriValidParamWithProvidedPattern_ReturnsUriWithParam()
    {
        $pattern = new Pattern\DynamicTargetMask('/{lang}/foo', ['lang' => '(en|pl|fr)']);
        $this->assertSame('/en/foo', (string) $pattern->uri(new Doubles\FakeUri(), ['en']));
    }

    public function testUriInvalidParamWithProvidedPattern_ThrowsException()
    {
        $pattern = new Pattern\DynamicTargetMask('/{lang}/foo', ['lang' => '(en|pl|fr)']);
        $this->expectException(Exception\InvalidUriParamsException::class);
        $pattern->uri(new Doubles\FakeUri(), ['es']);
    }

    public function testRelativePathIsMatched()
    {
        $pattern = $this->pattern('{#id}');
        $request = $pattern->matchedRequest($this->request('/foo/bar/234'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame(['id' => '234'], $request->getAttributes());

        $pattern = $this->pattern('end/{@of}/{$path}');
        $request = $pattern->matchedRequest($this->request('/root/end/of/path-slug'));
        $this->assertSame(['of' => 'of', 'path' => 'path-slug'], $request->getAttributes());
    }

    public function testUriFromRelativePathWithRootInPrototype_ReturnsUriWithAppendedPath()
    {
        $pattern   = $this->pattern('{#id}/{$slug}');
        $prototype = Doubles\FakeUri::fromString('/foo/bar');
        $params    = ['34', 'slug-string'];
        $this->assertSame('/foo/bar/34/slug-string', (string) $pattern->uri($prototype, $params));

        $pattern   = $this->pattern('{#id}/{$slug}?query=string');
        $prototype = Doubles\FakeUri::fromString('/foo/bar');
        $params    = ['34', 'slug-string'];
        $this->assertSame('/foo/bar/34/slug-string?query=string', (string) $pattern->uri($prototype, $params));
    }

    public function testUriFromRelativePathWithNoRootInPrototype_ReturnsUriWithAbsolutePath()
    {
        $pattern   = $this->pattern('foo/{#id}');
        $prototype = new Doubles\FakeUri();
        $this->assertSame('/foo/34', (string) $pattern->uri($prototype, ['34']));
    }

    private function pattern($pattern = '')
    {
        return new Pattern\DynamicTargetMask($pattern);
    }

    private function request($path)
    {
        return new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('//example.com' . $path));
    }
}
