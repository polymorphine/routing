<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing;


class Map
{
    private array $paths;

    /**
     * @param Map\Path[] $paths
     */
    public function __construct(array $paths = [])
    {
        $this->paths = $paths;
    }

    /**
     * @param Map\Path $path
     */
    public function addPath(Map\Path $path): void
    {
        $this->paths[] = $path;
    }

    /**
     * @return Map\Path[]
     */
    public function paths(): array
    {
        return $this->paths;
    }
}
