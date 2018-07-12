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

use Polymorphine\Routing\Route\Pattern;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class HostDomain implements Pattern
{
    private $domain;

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
        $host = $prototype->getHost();
        if ($host && $host !== $this->domain) {
            $message = 'Cannot overwrite prototype domain `%s` with `%s`';
            throw new UnreachableEndpointException(sprintf($message, $host, $this->domain));
        }

        return $prototype->withHost($this->domain);
    }
}
