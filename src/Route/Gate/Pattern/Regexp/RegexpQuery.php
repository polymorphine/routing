<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern\Regexp;

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface as Uri;


class RegexpQuery extends Route\Gate\Pattern\Regexp
{
    use Route\Gate\Pattern\UriTemplatePlaceholder;

    public function matchedRequest(Request $request): ?Request
    {
        if (!$query = $request->getUri()->getQuery()) { return null; }
        $elements = $this->queryParams($query);
        $pattern  = $this->queryParams($this->pattern);

        $segments = [];
        foreach ($pattern as $name => $value) {
            if (!array_key_exists($name, $elements)) { return null; }
            $segments[] = ($value === null) ? $name : $name . '=' . $elements[$name];
        }

        return $this->matchUriPart(implode('&', $segments), $request);
    }

    protected function replacePlaceholders(Uri $uri, array $placeholders): Uri
    {
        $query = str_replace(array_keys($placeholders), $placeholders, $this->pattern);
        if (!$queryString = $uri->getQuery()) {
            return $uri->withQuery($query);
        }

        $routeParams = $this->queryParams($query);
        $queryParams = $this->queryParams($queryString);

        $segments = [];
        foreach ($queryParams as $name => $value) {
            if (array_key_exists($name, $routeParams)) {
                $value = $this->resolveConflict($routeParams, $name, $value);
                unset($routeParams[$name]);
            }

            $segments[] = $this->querySegment($name, $value);
        }

        foreach ($routeParams as $name => $value) {
            $segments[] = $this->querySegment($name, $value);
        }

        return $uri->withQuery(implode('&', $segments));
    }

    private function queryParams(string $query): array
    {
        $segments = explode('&', $query);

        $params = [];
        foreach ($segments as $segment) {
            [$name, $value] = explode('=', $segment, 2) + [false, null];
            $params[$name] = $value;
        }

        return $params;
    }

    private function resolveConflict(array $routeParams, string $name, ?string $value): ?string
    {
        if ($value === null) { return $routeParams[$name]; }

        if (isset($routeParams[$name]) && $routeParams[$name] !== $value) {
            throw Route\Exception\InvalidUriPrototypeException::queryConflict($name, $value, $routeParams[$name]);
        }

        return $value;
    }

    private function querySegment(string $name, ?string $value): string
    {
        return $value === null ? $name : $name . '=' . $value;
    }
}
