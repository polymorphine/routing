<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate;

use Polymorphine\Routing\Route\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


interface Pattern
{
    public const DELIM_LEFT  = '{';
    public const DELIM_RIGHT = '}';

    public const TYPE_NAME    = '@';
    public const TYPE_NUMBER  = '#';
    public const TYPE_SLUG    = '$';
    public const TYPE_NUMERIC = '%';

    public const TYPE_REGEXP = [
        self::TYPE_NAME    => '[a-zA-Z0-9]+',
        self::TYPE_NUMBER  => '[1-9][0-9]*',
        self::TYPE_SLUG    => '[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]',
        self::TYPE_NUMERIC => '[0-9]+'
    ];

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
     * @throws Exception\UriBuildException
     *
     * @return UriInterface
     */
    public function uri(UriInterface $prototype, array $params): UriInterface;

    /**
     * Returns UriInterface with applied pattern replacing dynamic
     * parts of pattern with its placeholders.
     *
     * @param UriInterface $uri
     *
     * @throws Exception\InvalidUriPrototypeException
     *
     * @return UriInterface
     */
    public function templateUri(UriInterface $uri): UriInterface;
}
