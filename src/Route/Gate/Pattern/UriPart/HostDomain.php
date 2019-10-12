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
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Static domain pattern matching request's domain to given
 * value and building URI with it.
 */
class HostDomain implements Route\Gate\Pattern
{
    private $domain;

    /**
     * Domain is matched and built starting from top domain level
     * and will ignore subdomain part of host segment of request.
     *
     * @param string $domain
     */
    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $host = substr($request->getUri()->getHost(), -strlen($this->domain));
        return ($host === $this->domain) ? $request : null;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $prototype->withHost($this->domain);
    }

    public function templateUri(UriInterface $uri): UriInterface
    {
        $host = $uri->getHost();
        if ($host && $host !== $this->domain) {
            $message = 'Cannot overwrite prototype domain `%s` with `%s`';
            throw new Exception\UnreachableEndpointException(sprintf($message, $host, $this->domain));
        }

        return $uri->withHost($this->domain);
    }
}
