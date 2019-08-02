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
        return $prototype->withQuery($this->combinedQuery($prototype->getQuery(), $params));
    }

    private function combinedQuery(string $prototypeQuery, array $params): string
    {
        if (empty($prototypeQuery) && !$params) { return $this->query; }

        $required  = $this->queryValues($this->query);
        $prototype = $this->queryValues($prototypeQuery);

        foreach ($required as $name => $value) {
            if ($this->isDefined($prototype, $name, $value)) { continue; }
            $prototype[$name] = $value ?? $params[$name] ?? null;
        }

        $query = [];
        foreach ($prototype as $name => $value) {
            $query[] = isset($value) ? $name . '=' . $value : $name;
        }

        return implode('&', $query);
    }

    private function isDefined(array $prototype, string $name, ?string $value): bool
    {
        if (!isset($prototype[$name])) { return false; }
        if (isset($value) && $prototype[$name] !== $value) {
            $message = 'Query param conflict for `%s` key in `%s` query pattern';
            throw new Exception\UnreachableEndpointException(sprintf($message, $name, $this->query));
        }
        return true;
    }

    private function queryMatch($requestQuery): bool
    {
        if (empty($requestQuery)) { return false; }

        $requiredSegments = $this->queryValues($this->query);
        $requestSegments  = $this->queryValues($requestQuery);

        foreach ($requiredSegments as $key => $value) {
            if (!array_key_exists($key, $requestSegments)) { return false; }
            if (!isset($value)) { continue; }
            if ($value !== $requestSegments[$key]) { return false; }
        }

        return true;
    }

    private function queryValues(string $query): array
    {
        $segments = $query ? explode('&', $query) : [];

        $segmentValues = [];
        foreach ($segments as $segment) {
            [$name, $value] = explode('=', $segment) + [false, null];
            $segmentValues[$name] = $value;
        }

        return $segmentValues;
    }
}
