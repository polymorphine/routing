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


class RegexpQuery implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\UriTemplatePlaceholder;

    private $query;
    private $params;

    public function __construct(string $query, array $params)
    {
        $this->query  = $query;
        $this->params = $params;
    }

    public function matchedRequest(Request $request): ?Request
    {
        if (!$query = $this->comparableQuery($request)) { return null; }

        $pattern = $this->regexp();
        if (!preg_match($pattern, $query, $attributes)) { return null; }

        foreach (array_intersect_key($attributes, $this->params) as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return $request;
    }

    public function uri(Uri $prototype, array $params): Uri
    {
        $placeholders = [];
        foreach ($this->params as $name => $type) {
            $token = self::DELIM_LEFT . $name . self::DELIM_RIGHT;
            $placeholders[$token] = $this->validParam($name, $type, $params);
        }

        return $this->replacePlaceholders($prototype, $placeholders);
    }

    public function templateUri(Uri $uri): Uri
    {
        $placeholders = [];
        foreach ($this->params as $name => $type) {
            $token      = self::DELIM_LEFT . $name . self::DELIM_RIGHT;
            $presetType = array_search($type, self::TYPE_REGEXP, true);
            $definition = $presetType ? $presetType . $name : $name . ':' . $type;
            $placeholders[$token] = $this->placeholder($definition);
        }

        return $this->replacePlaceholders($uri, $placeholders);
    }

    private function comparableQuery(Request $request): ?string
    {
        if (!$query = $request->getUri()->getQuery()) { return null; }
        $elements = $this->queryParams($query);
        $pattern  = $this->queryParams($this->query);

        $segments = [];
        foreach ($pattern as $name => $value) {
            if (!array_key_exists($name, $elements)) { return null; }
            $segments[] = ($value === null) ? $name : $name . '=' . $elements[$name];
        }

        return implode('&', $segments);
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

    private function regexp(): string
    {
        $regexp = preg_quote($this->query);
        foreach ($this->params as $name => $paramRegexp) {
            $placeholder = '\\' . self::DELIM_LEFT . $name . '\\' . self::DELIM_RIGHT;
            $replace     = '(?P<' . $name . '>' . $paramRegexp . ')';
            $regexp      = str_replace($placeholder, $replace, $regexp);
        }

        return '#^' . $regexp . '$#';
    }

    private function validParam(string $name, string $type, array $params): string
    {
        if (!isset($params[$name])) {
            throw Route\Exception\InvalidUriParamException::missingParam($name);
        }

        $value = (string) $params[$name];
        if (!preg_match('/^' . $type . '$/', $value)) {
            throw Route\Exception\InvalidUriParamException::formatMismatch($name, $type);
        }

        return $value;
    }

    private function replacePlaceholders(Uri $uri, array $placeholders): Uri
    {
        $query = str_replace(array_keys($placeholders), $placeholders, $this->query);
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
