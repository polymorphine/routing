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
        $this->assertInstanceOf(Map::class, new Map());
    }

    public function testCanAddPaths()
    {
        $map = new Map();

        $path1 = new Map\Path('some.path', 'POST', Doubles\FakeUri::fromString('/foo/bar'));
        $map->addPath($path1);
        $this->assertSame([$path1], $map->paths());

        $path2 = new Map\Path('other.path', '*', Doubles\FakeUri::fromString('/foo/bar/baz'));
        $map->addPath($path2);
        $this->assertSame([$path1, $path2], $map->paths());
    }
}
