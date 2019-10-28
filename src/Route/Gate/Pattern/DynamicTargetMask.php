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
    /** @var Route\Gate\Pattern */
    private $parsed;
    private $pattern;
    private $params;

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
        return $this->pattern()->matchedRequest($request);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->pattern()->uri($prototype, $params);
    }

    public function templateUri(UriInterface $uri): UriInterface
    {
        return $this->pattern()->templateUri($uri);
    }

    public function pattern(): Route\Gate\Pattern
    {
        if ($this->parsed) { return $this->parsed; }

        $types  = array_keys(self::TYPE_REGEXP);
        $regexp = $this->typeMarkersRegexp($types);

        preg_match_all($regexp, $this->pattern, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->params[$match['id']] = self::TYPE_REGEXP[$match['type']];
        }

        $replace = array_map(function ($type) { return self::DELIM_LEFT . $type; }, $types);
        $pattern = str_replace($replace, self::DELIM_LEFT, $this->pattern);

        [$path, $query] = explode('?', $pattern, 2) + [false, null];

        $path = trim($path, '/');

        $parsed = array_filter([
            $path ? new Route\Gate\Pattern\Regexp\RegexpPath($path, $this->getParams($path)) : null,
            $query ? new Route\Gate\Pattern\Regexp\RegexpQuery($query, $this->getParams($query)) : null
        ]);

        return $this->parsed = count($parsed) === 1 ? array_pop($parsed) : new CompositePattern($parsed);
    }

    private function getParams(string $pattern): array
    {
        $params = [];
        foreach ($this->params as $name => $value) {
            $token = self::DELIM_LEFT . $name . self::DELIM_RIGHT;
            if (strpos($pattern, $token) !== false) {
                $params[$name] = $value;
            }
        }
        return $params;
    }

    private function typeMarkersRegexp(array $types): string
    {
        $regexpMarkers = array_map(function ($typeMarker) { return preg_quote($typeMarker, '/'); }, $types);
        $idPattern     = '(?P<type>' . implode('|', $regexpMarkers) . ')(?P<id>[a-zA-Z]+)';

        return '/' . self::DELIM_LEFT . $idPattern . self::DELIM_RIGHT . '/';
    }
}
