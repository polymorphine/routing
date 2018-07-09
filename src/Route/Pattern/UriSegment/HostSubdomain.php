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
use Polymorphine\Routing\Exception\InvalidUriParamsException;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class HostSubdomain implements Pattern
{
    private $id;
    private $values = [];

    /**
     * @param string   $id
     * @param string[] $values
     */
    public function __construct(string $id, array $values)
    {
        $this->id     = $id;
        $this->values = $values;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $host          = $request->getUri()->getHost();
        [$subdomain, ] = explode('.', $host, 2) + [null, null];

        if (!$subdomain || !in_array($subdomain, $this->values, true)) {
            return null;
        }
        return $request->withAttribute($this->id, $subdomain);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if (!isset($params[$this->id])) {
            throw new InvalidUriParamsException();
        }

        if (!$host = $prototype->getHost()) {
            throw new UnreachableEndpointException();
        }

        if (!in_array($params[$this->id], $this->values, true)) {
            throw new UnreachableEndpointException();
        }

        return $prototype->withHost($params[$this->id] . '.' . $host);
    }
}
