<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Map;
use Polymorphine\Routing\Route;


class MapTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Map::class, new Map());
    }

    public function testCanAddPaths()
    {
        $map = new Map();

        $path1 = new Map\Path('some.path', 'POST', (string) Doubles\FakeUri::fromString('/foo/bar'));
        $map->addPath($path1);
        $this->assertSame([$path1], $map->paths());

        $path2 = new Map\Path('other.path', '*', (string) Doubles\FakeUri::fromString('/foo/bar/baz'));
        $map->addPath($path2);
        $this->assertSame([$path1, $path2], $map->paths());
    }

    public function testTraceCanAddPaths()
    {
        $map   = new Map();
        $trace = new Map\Trace($map, Doubles\FakeUri::fromString($uri = '/foo/bar'));

        $trace->nextHop('some.path')->endpoint();
        $this->assertEquals([new Map\Path('some.path', '*', $uri)], $map->paths());

        $trace->nextHop('other.path')->endpoint();
        $this->assertEquals([new Map\Path('some.path', '*', $uri), new Map\Path('other.path', '*', $uri)], $map->paths());
    }

    public function testNoPathTraceResolvesToRoot()
    {
        $map   = new Map();
        $trace = new Map\Trace($map, Doubles\FakeUri::fromString($uri = '/foo/bar'), 'rootLabel');

        $trace->endpoint();
        $this->assertEquals([new Map\Path('rootLabel', '*', $uri)], $map->paths());
    }

    public function testFalsyPathTraceIsPreserved()
    {
        $map   = new Map();
        $trace = new Map\Trace($map, Doubles\FakeUri::fromString($uri = '/foo/bar'), 'rootLabel');

        $trace->nextHop('0')->endpoint();
        $this->assertEquals([new Map\Path('0', '*', $uri)], $map->paths());
    }

    public function testExcludedTraceHop_ThrowsException()
    {
        $trace = new Map\Trace(new Map(), new Doubles\FakeUri());
        $trace = $trace->withExcludedHops(['foo', 'bar', 'baz']);
        $this->expectException(Map\Exception\UnreachableEndpointException::class);
        $trace->nextHop('bar');
    }

    public function testExcludedHopsAreAddedUntilNextNamedHop()
    {
        $trace = new Map\Trace(new Map(), new Doubles\FakeUri());
        $trace = $trace->withExcludedHops(['foo', 'bar', 'baz']);
        $trace = $trace->withExcludedHops(['fizz', 'buzz']);
        $this->expectException(Map\Exception\UnreachableEndpointException::class);
        $trace->nextHop('foo');
    }

    public function testExcludedRootLabelForRootEndpoint_ThrowsException()
    {
        $trace = new Map\Trace(new Map(), new Doubles\FakeUri(), 'foo');
        $trace = $trace->withExcludedHops(['foo', 'bar', 'baz']);
        $this->expectException(Map\Exception\UnreachableEndpointException::class);
        $trace->endpoint();
    }

    public function testPathPatternForTraceWithLockedUriPath_ThrowsException()
    {
        $trace = new Map\Trace(new Map(), Doubles\FakeUri::fromString('/foo'));
        $trace = $trace->withLockedUriPath();
        $this->expectException(Map\Exception\UnreachableEndpointException::class);
        $trace->withPattern(new Doubles\MockedPattern('/baz'));
    }

    public function testTraceWithPatternPrototypeConflict_ThrowsException()
    {
        $trace   = new Map\Trace(new Map(), new Doubles\FakeUri());
        $pattern = new Doubles\MockedPattern();

        $pattern->exception = Route\Exception\InvalidUriPrototypeException::class;

        $this->expectException(Map\Exception\UnreachableEndpointException::class);
        $trace->withPattern($pattern);
    }
}
