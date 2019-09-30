<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Map;

use Psr\Http\Message\UriInterface;


class Path
{
    public $name;
    public $method;
    public $uri;

    public function __construct(
        string $name,
        string $method,
        UriInterface $uri
    ) {
        $this->name   = $name;
        $this->method = $method;
        $this->uri    = $uri;
    }
}
