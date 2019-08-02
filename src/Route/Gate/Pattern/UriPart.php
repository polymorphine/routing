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

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Abstract Pattern of URI part with exact value compared with
 * segment returned by request URI or attached to created link.
 */
abstract class UriPart implements Route\Gate\Pattern
{
    protected $pattern;

    /**
     * If built URI prototype already contains this segment value and
     * its different than given pattern UnreachableEndpointException
     * will be thrown.
     *
     * WARNING: Empty segment constraint checked before non-empty one
     * will not cause an exception despite contradiction and unreachable
     * endpoint URI.
     *
     * int|null patterns for port segment will be converted to string.
     *
     * @param string $pattern exact value that should be returned by UriInterface::getX() method
     */
    public function __construct(string $pattern)
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
        if ($uriPart && $uriPart !== $this->pattern) {
            $message = sprintf('Pattern conflict for `%s` in `%s` uri', (string) $this->pattern, (string) $prototype);
            throw new Exception\UnreachableEndpointException($message);
        }

        return $this->setUriPart($prototype);
    }

    /**
     * Returns string representing concrete URI segment defined by subclass.
     * NOTE: Port returned from UriInterface::getPort() method as int|null
     * is also converted to string.
     *
     * @param UriInterface $uri
     *
     * @return string
     */
    abstract protected function getUriPart(UriInterface $uri): string;

    /**
     * Creates instance with URI segment defined by subclass having instance
     * pattern value (pattern will be converted to int for port segment).
     *
     * @param UriInterface $uri
     *
     * @return UriInterface
     */
    abstract protected function setUriPart(UriInterface $uri): UriInterface;
}
