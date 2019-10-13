<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Dynamic Pattern performing request target matching with parameters
 * assignment and building URI using supplied dynamic parameters.
 *
 * NOTE: This pattern is intended as temporary solution handling hazy
 *       routing system of legacy APIs in the process of refactoring.
 */
class DynamicTargetMask implements Route\Gate\Pattern
{
    use UriTemplatePlaceholder;

    private $pattern;
    private $params;
    private $parsed = false;
    private $queryParams;

    /**
     * Dynamic parameters in pattern string are (prefixed) name placeholders
     * enclosed in delimiters defined in Pattern interface.
     *
     * Pattern interface defines also placeholder's prefixes that assign
     * common regexp patterns implicitly, so that $params parameter doesn't
     * need to define them.
     *
     * For queries only their values will be matched dynamically with
     * placeholders, and it is possible to test if key name exists within
     * matched request URI regardless of its value.
     *
     * @example - Prefixed placeholders and defined 'key' with any value:
     *          new DynamicTargetMask('/post/{#id}?title={$slug}&key=');
     *          - Equivalent with not-prefixed placeholders:
     *          new DynamicTargetMask('/post/{id}?title={slug}&key=', [
     *              'id'   => '[1-9][0-9]*',
     *              'slug' => '[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]'
     *          ]);
     *
     * @param string $pattern path (and query) pattern with variable placeholders
     * @param array  $params  associative array of not-prefixed placeholder name keys
     *                        and their regexp patterns
     */
    public function __construct(string $pattern, array $params = [])
    {
        $this->pattern = $pattern;
        $this->params  = $params;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $this->parsed or $this->parsePattern();

        $target = $this->comparableTarget($request);
        if (!$target) { return null; }

        $pattern = $this->patternRegexp();
        if (!preg_match($pattern, $target, $attributes)) { return null; }

        foreach (array_intersect_key($attributes, $this->params) as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return $request;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $this->parsed or $this->parsePattern();
        return $this->replacePlaceholders($prototype, $this->uriPlaceholders($params));
    }

    public function templateUri(UriInterface $uri): UriInterface
    {
        $this->parsed or $this->parsePattern();

        $placeholders = [];
        foreach ($this->params as $name => $type) {
            $token      = self::DELIM_LEFT . $name . self::DELIM_RIGHT;
            $presetType = array_search($type, self::TYPE_REGEXP, true);
            $definition = $presetType ? $presetType . $name : $name . ':' . $type;
            $placeholders[$token] = $this->placeholder($definition);
        }

        return $this->replacePlaceholders($uri, $placeholders);
    }

    private function patternRegexp()
    {
        $regexp = preg_quote($this->pattern);
        foreach ($this->params as $name => $paramRegexp) {
            $placeholder = '\\' . self::DELIM_LEFT . $name . '\\' . self::DELIM_RIGHT;
            $replace     = '(?P<' . $name . '>' . $paramRegexp . ')';
            $regexp      = str_replace($placeholder, $replace, $regexp);
        }

        if ($this->pattern[0] === '/') {
            $regexp = '^' . $regexp;
        }

        return '#' . $regexp . '$#';
    }

    private function uriPlaceholders(array $params): array
    {
        if (count($params) < count($this->params)) {
            $message = 'Route requires %s params for `%s` path - %s provided';
            $message = sprintf($message, count($this->params), $this->pattern, count($params));
            throw new Exception\InvalidUriParamException($message);
        }

        $placeholders = [];
        foreach ($this->params as $name => $type) {
            $param = $params[$name] ?? array_shift($params);
            $token = self::DELIM_LEFT . $name . self::DELIM_RIGHT;

            $placeholders[$token] = $this->validParam($name, $type, $param);
        }

        return $placeholders;
    }

    private function replacePlaceholders(UriInterface $uri, array $placeholders): UriInterface
    {
        $target = str_replace(array_keys($placeholders), $placeholders, $this->pattern);
        [$path, $query] = explode('?', $target, 2) + [false, null];

        $uri = $this->setPath($path, $uri);
        return $this->queryParams ? $this->setQuery($query, $uri) : $uri;
    }

    private function validParam(string $name, string $type, $value): string
    {
        $value = (string) $value;
        if (!preg_match('/^' . $type . '$/', $value)) {
            $message = 'Invalid param `%s` type for `%s` route path';
            throw new Exception\InvalidUriParamException(sprintf($message, $name, $this->pattern));
        }

        return $value;
    }

    private function comparableTarget(ServerRequestInterface $request): ?string
    {
        $uri  = $request->getUri();
        $path = $uri->getPath();

        if (!$this->queryParams) { return $path; }
        if (!$query = $this->relevantQueryParams($uri)) { return null; }

        return $path . '?' . $query;
    }

    private function relevantQueryParams(UriInterface $uri): ?string
    {
        if (!$query = $uri->getQuery()) { return null; }
        $elements = $this->queryParams($query);

        $segments = [];
        foreach ($this->queryParams as $name => $value) {
            if (!array_key_exists($name, $elements)) { return null; }
            $segments[] = ($value === null) ? $name : $name . '=' . $elements[$name];
        }

        return implode('&', $segments);
    }

    private function setPath(string $path, UriInterface $prototype): UriInterface
    {
        $prototypePath = $prototype->getPath();
        if ($path[0] !== '/') {
            return $prototype->withPath($prototypePath . '/' . $path);
        }

        if ($prototypePath && strpos($path, $prototypePath) !== 0) {
            $message = 'Uri conflict detected prototype `%s` path does not match route `%s` path';
            throw new Exception\InvalidUriPrototypeException(sprintf($message, $prototypePath, $path));
        }

        return $prototype->withPath($path);
    }

    private function setQuery(string $query, UriInterface $prototype): UriInterface
    {
        if (!$queryString = $prototype->getQuery()) {
            return $prototype->withQuery($query);
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

        return $prototype->withQuery(implode('&', $segments));
    }

    private function resolveConflict(array $routeParams, string $name, ?string $value): ?string
    {
        if ($value === null) { return $routeParams[$name]; }

        if (isset($routeParams[$name]) && $routeParams[$name] !== $value) {
            $message = 'Uri build conflict - attempt to overwrite `%s` query param value `%s` with `%s`';
            throw new Exception\InvalidUriPrototypeException(sprintf($message, $name, $value, $routeParams[$name]));
        }

        return $value;
    }

    private function parsePattern(): void
    {
        $types  = array_keys(self::TYPE_REGEXP);
        $regexp = $this->typeMarkersRegexp($types);

        $pos = strpos($this->pattern, '?');
        if ($pos !== false && $query = substr($this->pattern, $pos + 1)) {
            $this->queryParams = $this->queryParams($query);
        }

        preg_match_all($regexp, $this->pattern, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->params[$match['id']] = self::TYPE_REGEXP[$match['type']];
        }

        $replace = array_map(function ($type) { return self::DELIM_LEFT . $type; }, $types);

        $this->pattern = str_replace($replace, self::DELIM_LEFT, $this->pattern);
        $this->parsed  = true;
    }

    private function typeMarkersRegexp(array $types): string
    {
        $regexpMarkers = array_map(function ($typeMarker) { return preg_quote($typeMarker, '/'); }, $types);
        $idPattern     = '(?P<type>' . implode('|', $regexpMarkers) . ')(?P<id>[a-zA-Z]+)';

        return '/' . self::DELIM_LEFT . $idPattern . self::DELIM_RIGHT . '/';
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

    private function querySegment(string $name, ?string $value): string
    {
        return $value === null ? $name : $name . '=' . $value;
    }
}
