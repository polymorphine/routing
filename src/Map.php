<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing;

use Psr\Http\Message\UriInterface;


class Map
{
    private $endpoints;

    public function __construct(array $endpoints = [])
    {
        $this->endpoints = $endpoints;
    }

    public function addEndpoint(string $path, UriInterface $uri): void
    {
        $this->endpoints[$path] = (string) $uri;
    }

    public function toArray(): array
    {
        return $this->endpoints;
    }
}
