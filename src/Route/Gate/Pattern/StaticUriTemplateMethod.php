<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern;

use Psr\Http\Message\UriInterface;


trait StaticUriTemplateMethod
{
    public function templateUri(UriInterface $uri): UriInterface
    {
        return $this->uri($uri, []);
    }
}
