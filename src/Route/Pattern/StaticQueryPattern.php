<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Pattern;

use Polymorphine\Routing\Route\Pattern;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class StaticQueryPattern implements Pattern
{
    private $query;

    public function __construct(string $queryString)
    {
        $this->query = $queryString;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        return $this->queryMatch($request->getUri()->getQuery()) ? $request : null;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $prototype->withQuery($this->combinedQuery($prototype->getQuery()));
    }

    private function combinedQuery(string $prototypeQuery)
    {
        if (empty($prototypeQuery)) { return $this->query; }

        $requiredSegments  = $this->queryValues($this->query);
        $prototypeSegments = $this->queryValues($prototypeQuery);

        foreach ($requiredSegments as $name => $value) {
            if (isset($value, $prototypeSegments[$name]) && $prototypeSegments[$name] !== $value) {
                $message = 'Query param conflict for `%s` key in `%s` query';
                throw new UnreachableEndpointException(sprintf($message, $name, (string) $this->query));
            }

            if (!isset($value) && isset($prototypeSegments[$name])) {
                continue;
            }

            $prototypeSegments[$name] = $value;
        }

        $query = [];
        foreach ($prototypeSegments as $name => $value) {
            $query[] = isset($value) ? $name . '=' . $value : $name;
        }

        return implode('&', $query);
    }

    private function queryMatch($requestQuery)
    {
        if (empty($requestQuery)) { return false; }

        $requiredSegments = $this->queryValues($this->query);
        $requestSegments  = $this->queryValues($requestQuery);

        foreach ($requiredSegments as $key => $value) {
            if (!isset($requestSegments[$key])) { return false; }
            if (!isset($value)) { continue; }
            if ($value !== $requestSegments[$key]) { return false; }
        }

        return true;
    }

    private function queryValues(string $query): array
    {
        $segments = explode('&', $query);

        $segmentValues = [];
        foreach ($segments as $segment) {
            [$name, $value]       = explode('=', $segment) + [false, null];
            $segmentValues[$name] = $value;
        }

        return $segmentValues;
    }
}
