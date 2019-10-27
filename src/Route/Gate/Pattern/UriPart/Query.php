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
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface as Uri;


/**
 * Static query pattern that checks whether query param exists
 * with required value and builds URI based on query keys with
 * its values.
 */
class Query implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\UriTemplatePlaceholder;

    private $params;

    /**
     * Given query string params will be matched against defined params
     * only. Keys without defined value (null) will check only if these
     * keys are defined within query string, and keys with empty string
     * will require that value to be empty.
     *
     * Building URI on prototype with defined key-value pair not matching
     * current constraint will throw UnreachableEndpointException
     *
     * @param array $params associative array of query params and their values
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Query string parameter MUST NOT begin with `?` character.
     *
     * Given query string will be exploded into associative array with
     * undefined values (foo&bar) as null and empty (foo=&bar=) values
     * as empty string.
     *
     * @param string $query
     *
     * @return self
     */
    public static function fromQueryString(string $query): self
    {
        return new self(self::queryValues($query));
    }

    public function matchedRequest(Request $request): ?Request
    {
        if (!$query = $request->getUri()->getQuery()) { return null; }

        $segments = self::queryValues($query);
        foreach ($this->params as $name => $expected) {
            if (!array_key_exists($name, $segments)) { return null; }
            if (is_null($expected)) { continue; }
            if ($expected !== $segments[$name]) { return null; }
        }

        return $request;
    }

    public function uri(Uri $prototype, array $params): Uri
    {
        $query = $prototype->getQuery();
        if (!$query && !$params) {
            return $prototype->withQuery($this->queryString($this->params));
        }

        $values = self::queryValues($query);
        foreach ($this->params as $name => $expected) {
            if ($this->isDefined($values, $name, $expected)) { continue; }
            $values[$name] = $expected ?? $params[$name] ?? null;
        }

        return $prototype->withQuery($this->queryString($values));
    }

    public function templateUri(Uri $uri): Uri
    {
        $wildcardSegments = array_keys(array_filter($this->params, 'is_null'));
        return $this->uri($uri, array_fill_keys($wildcardSegments, $this->placeholder('*')));
    }

    private function isDefined(array $prototype, string $name, ?string $expected): bool
    {
        if (!isset($prototype[$name])) { return false; }
        if (!is_null($expected) && $prototype[$name] !== $expected) {
            throw Route\Exception\InvalidUriPrototypeException::queryConflict($name, $prototype[$name], $expected);
        }
        return true;
    }

    private function queryString(array $params): string
    {
        $query = [];
        foreach ($params as $name => $value) {
            $query[] = isset($value) ? $name . '=' . $value : $name;
        }

        return implode('&', $query);
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
}
