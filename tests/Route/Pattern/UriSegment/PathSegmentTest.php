<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Segment\UriSegment;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;


class PathSegmentTest extends TestCase
{
    public function testFirstNumericPathSegmentIsMatchedAndCapturedFromRelativePath()
    {
        $request = $this->request('/post/7523/some-slug-part')->withAttribute(Route::PATH_ATTRIBUTE, '7523/some-slug-part');
        $matched = $this->pattern('name')->matchedRequest($request);
        $this->assertSame('7523', $matched->getAttribute('name'));
        $this->assertSame('some-slug-part', $matched->getAttribute(Route::PATH_ATTRIBUTE));
    }

    public function testFirstNonNumericRelativePathSegmentIsNotMatched()
    {
        $request = $this->request('/post/foo/7523/anything')->withAttribute(Route::PATH_ATTRIBUTE, 'foo/7523/anything');
        $this->assertNull($this->pattern()->matchedRequest($request));
    }

    public function testUri_ReturnsUriWithAppendedIdParam()
    {
        $uri = $this->pattern('id', '[0-9]+')->uri($this->uri('/foo/bar'), ['id' => '00765']);
        $this->assertSame('/foo/bar/00765', $uri->getPath());

        $uri = $this->pattern()->uri($this->uri('/foo/bar'), ['id' => 225]);
        $this->assertSame('/foo/bar/225', $uri->getPath());
    }

    public function testUriWithoutIdParam_ThrowsException()
    {
        $this->expectException(Exception\InvalidUriParamsException::class);
        $this->pattern()->uri($this->uri('/foo/bar'), ['foo' => '00765']);
    }

    public function testUriWithNonNumericIdParam_ThrowsException()
    {
        $this->expectException(Exception\InvalidUriParamsException::class);
        $this->pattern()->uri($this->uri('/foo/bar'), ['id' => 'id-00765']);
    }

    public function testNamedConstructorsEquivalentToConcretePatterns()
    {
        $this->assertEquals($this->pattern('id', '[0-9]+'), PathSegment::numeric());
        $this->assertEquals($this->pattern('id', '[1-9][0-9]*'), PathSegment::number());
        $this->assertEquals($this->pattern('slug', '[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]'), PathSegment::slug());
        $this->assertEquals($this->pattern('name', '[a-zA-Z0-9]+'), PathSegment::name());
    }

    private function pattern(string $name = 'id', string $regexp = '[1-9][0-9]*')
    {
        return new PathSegment($name, $regexp);
    }

    private function request(string $uri)
    {
        $request      = new Doubles\FakeServerRequest();
        $request->uri = $this->uri($uri);
        return $request;
    }

    private function uri(string $uri)
    {
        return Doubles\FakeUri::fromString($uri);
    }
}
