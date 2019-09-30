<?php

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


class MapTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Map::class, $this->map());
    }

    public function testCanAddEndpoints()
    {
        $map = $this->map();

        $map->addEndpoint(new Map\Path('some.path', 'POST', Doubles\FakeUri::fromString('/foo/bar')));
        $expected = ['some.path' => ['uri' => '/foo/bar', 'method' => 'POST']];
        $this->assertSame($expected, $map->toArray());

        $map->addEndpoint(new Map\Path('other.path', '*', Doubles\FakeUri::fromString('/foo/bar/baz')));
        $expected += ['other.path' => ['uri' => '/foo/bar/baz', 'method' => '*']];
        $this->assertSame($expected, $map->toArray());
    }

    private function map(array $routes = []): Map
    {
        return new Map($routes);
    }
}
