<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern\UriSegment;

use Polymorphine\Routing\Route\Gate\Pattern\UriSegment;
use Psr\Http\Message\UriInterface;


/**
 * Subclass of UriSegment Pattern matching and generating
 * URI's port segment.
 */
class Port extends UriSegment
{
    protected function getUriPart(UriInterface $uri): string
    {
        return (string) $uri->getPort();
    }

    protected function setUriPart(UriInterface $uri): UriInterface
    {
        return $uri->withPort($this->pattern ? (int) $this->pattern : null);
    }
}
