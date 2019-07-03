<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern\UriSegment;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Static query pattern that checks whether query param exists
 * with required value and builds URI based on query keys with
 * its values.
 */
class Query implements Route\Gate\Pattern
{
    private $query;

    /**
     * Query string parameter MUST NOT begin with `?` character.
     *
     * Given query string will be matched against defined params only
     * without specified order. Keys without equal sign (eg. `foo&bar`)
     * will check only if these keys are defined within query string, and
     * keys with empty value (like `foo` in `foo=&bar=something`) will
     * require that value to be empty.
     *
     * Building URI on prototype with defined key-value pair not matching
     * current constraint will throw UnreachableEndpointException
     *
     * @param string $queryString
     */
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
                throw new Exception\UnreachableEndpointException(sprintf($message, $name, (string) $this->query));
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
            [$name, $value] = explode('=', $segment) + [false, null];
            $segmentValues[$name] = $value;
        }

        return $segmentValues;
    }
}
