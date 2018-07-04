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

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


abstract class UriSegment implements Route\Pattern
{
    protected $pattern;

    public function __construct($pattern)
    {
        $this->pattern = $pattern;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $uriPart = $this->getUriPart($request->getUri());
        return ($this->pattern === $uriPart) ? $request : null;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $uriPart = $this->getUriPart($prototype);
        if (isset($uriPart) && $uriPart !== $this->pattern) {
            $message = sprintf('Pattern conflict for `%s` in `%s` uri', (string) $this->pattern, (string) $prototype);
            throw new Exception\UnreachableEndpointException($message);
        }

        return $this->setUriPart($prototype);
    }

    abstract protected function getUriPart(UriInterface $uri);

    abstract protected function setUriPart(UriInterface $uri): UriInterface;
}
