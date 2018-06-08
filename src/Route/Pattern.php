<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


interface Pattern
{
    /**
     * When $request matches pattern it is returned back
     * Null is returned otherwise.
     *
     * If pattern contains variable parts its values must
     * be added as named attributes, thus each variable part
     * has to define its name.
     *
     * @param ServerRequestInterface $request
     *
     * @return null|ServerRequestInterface
     */
    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface;

    /**
     * Returns UriInterface replacing dynamic parts of pattern
     * with given $params, thus number of $params must match
     * dynamically assigned values.
     *
     * If array of given parameters is not associative
     * each one will be assigned in order of their appearance
     * in pattern.
     *
     * @param array        $params
     * @param UriInterface $prototype
     *
     * @return UriInterface
     */
    public function uri(UriInterface $prototype, array $params): UriInterface;
}
