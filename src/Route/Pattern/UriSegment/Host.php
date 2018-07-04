<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Pattern\UriSegment;

use Polymorphine\Routing\Route\Pattern\UriSegment;
use Psr\Http\Message\UriInterface;


class Host extends UriSegment
{
    protected function getUriPart(UriInterface $uri): ?string
    {
        return $uri->getHost() ?: null;
    }

    protected function setUriPart(UriInterface $uri): UriInterface
    {
        return $uri->withHost($this->pattern);
    }
}
