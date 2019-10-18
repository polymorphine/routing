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
    use Route\Gate\Pattern\UriTemplatePlaceholder;

    private $query;

    /**
     * Given query string params will be matched against defined params
     * only. Keys without defined value (null) will check only if these
     * keys are defined within query string, and keys with empty string
     * will require that value to be empty.
     *
     * Building URI on prototype with defined key-value pair not matching
     * current constraint will throw UnreachableEndpointException
     *
     * @param array $queryValues
     */
    public function __construct(array $queryValues)
    {
        $this->query = $queryValues;
    }

    /**
     * Query string parameter MUST NOT begin with `?` character.
     *
     * Given query string will be exploded into associative array with
     * undefined values (foo&bar) as null and empty (foo=&bar=) values
     * as empty string.
     *
     *
     * @param string $query
     *
     * @return self
     */
    public static function fromQueryString(string $query): self
    {
        return new self(self::queryValues($query));
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        return $this->queryMatch($request->getUri()->getQuery()) ? $request : null;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $prototype->withQuery($this->combinedQuery($prototype->getQuery(), $params));
    }

    public function templateUri(UriInterface $uri): UriInterface
    {
        $wildcardSegments = array_keys(array_filter($this->query, 'is_null'));
        return $this->uri($uri, array_fill_keys($wildcardSegments, $this->placeholder('*')));
    }

    private function combinedQuery(string $prototypeQuery, array $params): string
    {
        if (empty($prototypeQuery) && !$params) {
            return $this->queryString($this->query);
        }

        $prototype = $this->queryValues($prototypeQuery);
        foreach ($this->query as $name => $value) {
            if ($this->isDefined($prototype, $name, $value)) { continue; }
            $prototype[$name] = $value ?? $params[$name] ?? null;
        }

        return $this->queryString($prototype);
    }

    private function isDefined(array $prototype, string $name, ?string $value): bool
    {
        if (!isset($prototype[$name])) { return false; }
        if (isset($value) && $prototype[$name] !== $value) {
            throw Exception\InvalidUriPrototypeException::queryConflict($name, $prototype[$name], $value);
        }
        return true;
    }

    private function queryMatch($requestQuery): bool
    {
        if (empty($requestQuery)) { return false; }

        $requestSegments = self::queryValues($requestQuery);
        foreach ($this->query as $key => $value) {
            if (!array_key_exists($key, $requestSegments)) { return false; }
            if (!isset($value)) { continue; }
            if ($value !== $requestSegments[$key]) { return false; }
        }

        return true;
    }

    private static function queryValues(string $query): array
    {
        $segments = $query ? explode('&', $query) : [];

        $segmentValues = [];
        foreach ($segments as $segment) {
            [$name, $value] = explode('=', $segment) + [false, null];
            $segmentValues[$name] = $value;
        }

        return $segmentValues;
    }

    private function queryString(array $values): string
    {
        $query = [];
        foreach ($values as $name => $value) {
            $query[] = isset($value) ? $name . '=' . $value : $name;
        }

        return implode('&', $query);
    }
}
