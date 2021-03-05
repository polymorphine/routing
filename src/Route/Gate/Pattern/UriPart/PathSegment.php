<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern\UriPart;

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Static pattern constraint with build directive for single path segment.
 */
class PathSegment implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\PathContextMethods;

    private $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        return $this->name === $this->pathSegment($request)
            ? $this->newContextRequest($request)
            : null;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $prototype->withPath($prototype->getPath() . '/' . $this->name);
    }

    public function templateUri(UriInterface $uri): UriInterface
    {
        return $this->uri($uri, []);
    }
}
