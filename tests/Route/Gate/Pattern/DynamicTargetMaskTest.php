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
use Polymorphine\Routing\Route\Gate\Pattern;
use Polymorphine\Routing\Route\Exception;
use Polymorphine\Routing\Tests\Doubles;
use Psr\Http\Message\ServerRequestInterface;


class DynamicTargetMaskTest extends TestCase
{
    use Pattern\UriTemplatePlaceholder;

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

        $patternAttributes = $this->attributes($pattern->matchedRequest($request));
        $this->assertSame($attr, $patternAttributes);
    }

    public function testPatternWithNoQueryParamValueMatchesAnyValue()
    {
        $pattern = $this->pattern('?foo&fizz');
        $request = $this->request('https://example.com/path?fizz=buzz&foo=bar-125&anything=else');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    public function testPatternWithEmptyQueryParamsMatchEmptyValue()
    {
        $pattern = $this->pattern('?foo=');
        $request = $this->request('https://example.com/path?fizz=buzz&foo=&anything=else');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));

        $pattern = $this->pattern('?foo=');
        $request = $this->request('https://example.com/path?fizz=buzz&foo=123&anything=else');
        $this->assertNull($pattern->matchedRequest($request));
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
        $this->assertSame($uri, (string) $pattern->uri(new Doubles\FakeUri(), $attr));
    }

    public function matchingRequests()
    {
        return [
            'no_params'   => ['/path/only', '/path/only', []],
            'id'          => ['/page/{#no}', '/page/4', ['no' => '4']],
            'rev_id+slug' => ['/page/{#no}/{$title}', '/page/576/foo-bar-45', ['no' => '576', 'title' => 'foo-bar-45']],
            'prefixed_id' => ['/foo-{@name}', '/foo-bar5000', ['name' => 'bar5000']],
            'query'       => ['/path/and?user={#id}', '/path/and?user=938', ['id' => '938']],
            'query+path'  => ['/path/user/{#id}?foo={$bar}', '/path/user/938?foo=bar-BAZ', ['bar' => 'bar-BAZ', 'id' => '938']]
        ];
    }

    public function testUriMissingPathParam_ThrowsException()
    {
        $pattern = $this->pattern('/some-{#number}/{$slug}');
        $this->expectException(Exception\InvalidUriParamException::class);
        $pattern->uri(new Doubles\FakeUri(), ['number' => 22, 'not_slug' => 'foo']);
    }

    public function testUriMissingQueryParam_ThrowsException()
    {
        $pattern = $this->pattern('?some={#number}&value={$slug}');
        $this->expectException(Exception\InvalidUriParamException::class);
        $pattern->uri(new Doubles\FakeUri(), ['number' => 22, 'not_slug' => 'foo']);
    }

    public function testUriInvalidTypedParam_ThrowsException()
    {
        $pattern = $this->pattern('/user/{#countryId}');
        $this->expectException(Exception\InvalidUriParamException::class);
        $pattern->uri(new Doubles\FakeUri(), ['countryId' => 'Poland']);
    }

    public function testQueryStringMatchIgnoresParamOrder()
    {
        $pattern = $this->pattern('/path/and?user={#id}&foo={$bar}');
        $request = $this->request('/path/and?foo=bar-BAZ&user=938');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame(['bar' => 'bar-BAZ', 'id' => '938'], $this->attributes($matched));
    }

    public function testQueryStringIsIgnoredWhenNotSpecifiedInRoute()
    {
        $pattern = $this->pattern('/path/{@directory}');
        $request = $this->request('/path/something?foo=bar-BAZ&user=938');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame(['directory' => 'something'], $this->attributes($matched));
    }

    public function testNotSpecifiedQueryParamsAreIgnored()
    {
        $pattern = $this->pattern('/path/only?name={$slug}&user=938');
        $request = $this->request('/path/only?foo=bar-BAZ&user=938&name=shudd3r');
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame(['slug' => 'shudd3r'], $this->attributes($matched));
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
        $this->assertSame(['bar' => 'bar', 'name' => 'slug-example'], $this->attributes($request));

        $uri = $pattern->uri(Doubles\FakeUri::fromString('http://example.com'), ['bar' => 'something', 'name' => 'slug-string']);
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
        $this->expectException(Exception\InvalidUriPrototypeException::class);
        $pattern->uri(Doubles\FakeUri::fromString($uri), ['id' => 1500]);
    }

    public function prototypeConflict()
    {
        return [
            'expected fizz=buzz'  => ['/foo/bar?anything&fizz=buzz', '?anything=foo&fizz=baz'],
            'expected empty fizz' => ['/foo/{#id}?some=query&fizz=buzz', '?some=query&fizz=']
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
            ['{#id}', '/user', ['id' => 1500], '/user/1500'],
            ['foo/{#id}?some=foo', '?other=bar', ['id' => 673], '/foo/673?other=bar&some=foo'],
            ['bar/baz', '/foo', [], '/foo/bar/baz'],
            ['foo/{#id}?some=query&fizz=buzz', '?some=query&fizz', ['id' => 123], '/foo/123?some=query&fizz=buzz'],
            ['foo?fizz', '?fizz=foo&buzz=something', [], '/foo?fizz=foo&buzz=something']
        ];
    }

    public function testMatchWithProvidedPattern()
    {
        $pattern = new Pattern\DynamicTargetMask('/some/path/{hex}', ['hex' => '[A-F0-9]+']);
        $request = $this->request('/some/path/D6E8A9F6');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
        $this->assertSame(['hex' => 'D6E8A9F6'], $this->attributes($pattern->matchedRequest($request)));

        $request = $this->request('/some/path/d6e8a9f6');
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testUriValidParamWithProvidedPattern_ReturnsUriWithParam()
    {
        $pattern = new Pattern\DynamicTargetMask('/{lang}/foo', ['lang' => '(en|pl|fr)']);
        $this->assertSame('/en/foo', (string) $pattern->uri(new Doubles\FakeUri(), ['lang' => 'en']));
    }

    public function testUriInvalidPathParamWithProvidedPattern_ThrowsException()
    {
        $pattern = new Pattern\DynamicTargetMask('/{lang}/foo', ['lang' => '(en|pl|fr)']);
        $this->expectException(Exception\InvalidUriParamException::class);
        $pattern->uri(new Doubles\FakeUri(), ['lang' => 'es']);
    }

    public function testUriInvalidQueryParamWithProvidedPattern_ThrowsException()
    {
        $pattern = new Pattern\DynamicTargetMask('?foo={lang}', ['lang' => '(en|pl|fr)']);
        $this->expectException(Exception\InvalidUriParamException::class);
        $pattern->uri(new Doubles\FakeUri(), ['lang' => 'es']);
    }

    public function testRelativePathIsMatched()
    {
        $pattern = $this->pattern('{#id}');
        $request = $this->request('/foo/bar/234/baz')->withAttribute(Route::PATH_ATTRIBUTE, ['234', 'baz']);
        $matched = $pattern->matchedRequest($request);
        $this->assertInstanceOf(ServerRequestInterface::class, $matched);
        $this->assertSame([Route::PATH_ATTRIBUTE => ['baz'], 'id' => '234'], $matched->getAttributes());

        $pattern = $this->pattern('end/{@of}/{$path}');
        $request = $this->request('/root/end/of/path-slug')->withAttribute(Route::PATH_ATTRIBUTE, ['end', 'of', 'path-slug']);
        $matched = $pattern->matchedRequest($request);
        $this->assertSame([Route::PATH_ATTRIBUTE => [], 'of' => 'of', 'path' => 'path-slug'], $matched->getAttributes());
    }

    public function testUriFromRelativePathWithRootInPrototype_ReturnsUriWithAppendedPath()
    {
        $pattern   = $this->pattern('{#id}/{$slug}');
        $prototype = Doubles\FakeUri::fromString('/foo/bar');
        $params    = ['id' => '34', 'slug' => 'slug-string'];
        $this->assertSame('/foo/bar/34/slug-string', (string) $pattern->uri($prototype, $params));

        $pattern   = $this->pattern('{#id}/{$slug}?query=string');
        $prototype = Doubles\FakeUri::fromString('/foo/bar');
        $params    = ['id' => '34', 'slug' => 'slug-string'];
        $this->assertSame('/foo/bar/34/slug-string?query=string', (string) $pattern->uri($prototype, $params));
    }

    public function testUriFromRelativePathWithNoRootInPrototype_ReturnsUriWithAbsolutePath()
    {
        $pattern   = $this->pattern('foo/{#id}');
        $prototype = new Doubles\FakeUri();
        $this->assertSame('/foo/34', (string) $pattern->uri($prototype, ['id' => '34']));
    }

    public function testTemplateUri_ReturnsPatternUriWithParameterPlaceholders()
    {
        $pattern = new Pattern\DynamicTargetMask('bar/{id}?name={name}', ['id' => '[1-9]', 'name' => '(bar|baz)']);
        $uri     = Doubles\FakeUri::fromString('//example.com/foo?query=string');
        $expected = $uri->withPath('/foo/bar/' . $this->placeholder('id:[1-9]'))
                        ->withQuery('query=string&name=' . $this->placeholder('name:(bar|baz)'));
        $this->assertEquals($expected, $pattern->templateUri($uri));
    }

    public function testTemplateUriWithPredefinedRegexp_ReturnsUriWithParameterTypePlaceholder()
    {
        $pattern = $this->pattern('bar/{#id}?name={$name}');
        $uri     = Doubles\FakeUri::fromString('//example.com/foo?query=string');
        $expected = $uri->withPath('/foo/bar/' . $this->placeholder('#id'))
                        ->withQuery('query=string&name=' . $this->placeholder('$name'));
        $this->assertEquals($expected, $pattern->templateUri($uri));
    }

    /**
     * @dataProvider prototypeConflict
     *
     * @param $pattern
     * @param $uri
     */
    public function testTemplateUriOverwritingPrototypeSegment_ThrowsException($pattern, $uri)
    {
        $pattern = $this->pattern($pattern);
        $this->expectException(Exception\InvalidUriPrototypeException::class);
        $pattern->templateUri(Doubles\FakeUri::fromString($uri));
    }

    private function pattern($pattern = '')
    {
        return new Pattern\DynamicTargetMask($pattern);
    }

    private function request($path)
    {
        return new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('//example.com' . $path));
    }

    private function attributes(ServerRequestInterface $request): array
    {
        $attributes = $request->getAttributes();
        unset($attributes[Route::PATH_ATTRIBUTE]);
        ksort($attributes);
        return $attributes;
    }
}
