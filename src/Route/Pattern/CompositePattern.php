<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Pattern;

use Polymorphine\Routing\Route\Pattern;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class CompositePattern implements Pattern
{
    private $patterns;

    /**
     * @param Pattern[] $patterns
     */
    public function __construct(array $patterns)
    {
        $this->patterns = $patterns;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        foreach ($this->patterns as $pattern) {
            $request = $pattern->matchedRequest($request);
            if (!$request) { return null; }
        }

        return $request;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        foreach ($this->patterns as $pattern) {
            $prototype = $pattern->uri($prototype, $params);
        }

        return $prototype;
    }
}
