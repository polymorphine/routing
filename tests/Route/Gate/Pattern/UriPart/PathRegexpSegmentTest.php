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
use Polymorphine\Routing\Route\Gate\Pattern\UriPart\PathRegexpSegment;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;


class PathRegexpSegmentTest extends TestCase
{
    public function testFirstNumericPathSegmentIsMatchedAndCapturedFromRelativePath()
    {
        $request = $this->request('/post/7523/some-slug-part')->withAttribute(Route::PATH_ATTRIBUTE, ['7523', 'some-slug-part']);
        $matched = $this->pattern('name')->matchedRequest($request);
        $this->assertSame('7523', $matched->getAttribute('name'));
        $this->assertSame(['some-slug-part'], $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testFirstNonNumericRelativePathSegmentIsNotMatched()
    {
        $request = $this->request('/post/foo/7523/anything')->withAttribute(Route::PATH_ATTRIBUTE, ['foo', '7523', 'anything']);
        $this->assertNull($this->pattern()->matchedRequest($request));
    }

    public function testUriWithValidParamValue_ReturnsUriWithAppendedParam()
    {
        $uri = $this->pattern('id', '[0-9]+')->uri($this->uri('/foo/bar'), ['id' => '00765']);
        $this->assertSame('/foo/bar/00765', $uri->getPath());

        $uri = $this->pattern()->uri($this->uri('/foo/bar'), ['id' => 225]);
        $this->assertSame('/foo/bar/225', $uri->getPath());
    }

    public function testUriWithoutRequiredParam_ThrowsException()
    {
        $this->expectException(Exception\InvalidUriParamsException::class);
        $this->pattern()->uri($this->uri('/foo/bar'), ['foo' => '00765']);
    }

    public function testUriWithNotMatchingParam_ThrowsException()
    {
        $this->expectException(Exception\InvalidUriParamsException::class);
        $this->pattern()->uri($this->uri('/foo/bar'), ['id' => 'id-00765']);
    }

    public function testNamedConstructorsEquivalentToConcretePatterns()
    {
        $this->assertEquals($this->pattern('id', '[0-9]+'), PathRegexpSegment::numeric());
        $this->assertEquals($this->pattern('id', '[1-9][0-9]*'), PathRegexpSegment::number());
        $this->assertEquals($this->pattern('slug', '[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]'), PathRegexpSegment::slug());
        $this->assertEquals($this->pattern('name', '[a-zA-Z0-9]+'), PathRegexpSegment::name());
    }

    private function pattern(string $name = 'id', string $regexp = '[1-9][0-9]*')
    {
        return new PathRegexpSegment($name, $regexp);
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
