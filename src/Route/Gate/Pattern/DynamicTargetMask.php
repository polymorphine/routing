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

        $params = $this->uriPlaceholders($params);
        $target = str_replace(array_keys($params), $params, $this->pattern);

        if ($target[0] !== '/') {
            return $this->resolveRelativePath($target, $prototype);
        }

        if (!$this->queryParams) {
            $this->checkConflict($target, $prototype->getPath());
            return $prototype->withPath($target);
        }

        [$path, $query] = explode('?', $target, 2);

        $this->checkConflict($path, $prototype->getPath());
        $this->checkConflict($query, $prototype->getQuery());

        return $prototype->withPath($path)->withQuery($query);
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
            throw new Exception\InvalidUriParamsException($message);
        }

        $placeholders = [];
        foreach ($this->params as $name => $type) {
            $param = $params[$name] ?? array_shift($params);
            $token = self::DELIM_LEFT . $name . self::DELIM_RIGHT;

            $placeholders[$token] = $this->validParam($name, $type, $param);
        }

        return $placeholders;
    }

    private function validParam(string $name, string $type, $value): string
    {
        $value = (string) $value;
        if (!preg_match('/^' . $type . '$/', $value)) {
            $message = 'Invalid param `%s` type for `%s` route path';
            throw new Exception\InvalidUriParamsException(sprintf($message, $name, $this->pattern));
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
        $elements = $this->queryParams(explode('&', $query));

        $segments = [];
        foreach ($this->queryParams as $name => $value) {
            if (!array_key_exists($name, $elements)) { return null; }
            $segments[] = ($value === null) ? $name : $name . '=' . $elements[$name];
        }

        return implode('&', $segments);
    }

    private function resolveRelativePath($target, UriInterface $prototype): UriInterface
    {
        $target = $prototype->getPath() . '/' . $target;

        if (!$this->queryParams) { return $prototype->withPath($target); }

        [$path, $query] = explode('?', $target, 2);
        $this->checkConflict($query, $prototype->getQuery());

        return $prototype->withPath($path)->withQuery($query);
    }

    private function checkConflict(string $routeSegment, string $prototypeSegment)
    {
        if ($prototypeSegment && strpos($routeSegment, $prototypeSegment) !== 0) {
            $message = 'Uri conflict detected prototype `%s` does not match route `%s`';
            throw new Exception\UnreachableEndpointException(sprintf($message, $prototypeSegment, $routeSegment));
        }
    }

    private function parsePattern(): void
    {
        $types  = array_keys(self::TYPE_REGEXP);
        $regexp = $this->typeMarkersRegexp($types);

        $pos = strpos($this->pattern, '?');
        if ($pos !== false && $query = substr($this->pattern, $pos + 1)) {
            $this->queryParams = $this->queryParams(explode('&', $query));
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

    private function queryParams(array $segments): array
    {
        $params = [];
        foreach ($segments as $segment) {
            [$name, $value] = explode('=', $segment, 2) + [false, null];
            $params[$name] = $value;
        }

        return $params;
    }
}
