<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern;

use Polymorphine\Routing\Route\Gate\Pattern;
use Polymorphine\Routing\Route\Gate\Pattern\UriPart as Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;


/**
 * Lazy pattern parsing pattern templates for individual URI parts.
 */
class UriPattern implements Pattern
{
    private array $uri;
    private array $regexp;

    private Pattern $pattern;

    /**
     * @param array $segments associative array of URI segments as returned by parse_url() function
     * @param array $regexp   associative array of REGEXP patterns for uri parameters
     */
    public function __construct(array $segments, array $regexp = [])
    {
        $this->uri    = $segments;
        $this->regexp = $regexp;
    }

    public static function fromUriString(string $uri, array $regexp = []): self
    {
        self::parseTypedParams($uri, $regexp);
        if (!$segments = parse_url($uri)) {
            throw new InvalidArgumentException(sprintf('Malformed URI string: `%s`', $uri));
        }

        return new self($segments, $regexp);
    }

    public static function path(string $path, array $regexp = []): self
    {
        self::parseTypedParams($path, $regexp);
        return new self(['path' => $path], $regexp);
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

    private function pattern(): Pattern
    {
        return $this->pattern ??= $this->resolvedPattern();
    }

    private function resolveUriPart(string $name, $value): ?Pattern
    {
        if (!$value) { return null; }

        switch ($name) {
            case 'scheme':
                return new Uri\Scheme($value);
            case 'host':
                return ($value[0] === '*') ? new Uri\HostDomain(ltrim($value, '*')) : new Uri\Host($value);
            case 'port':
                return new Uri\Port((string) $value);
            case 'user':
                $pass = isset($this->uri['pass']) ? ':' . $this->uri['pass'] : '';
                return new Uri\UserInfo($value . $pass);
            case 'path':
                return $value ? $this->pathPattern($value) : null;
            case 'query':
                return $this->queryPattern($value);
        }

        return null;
    }

    private function pathPattern(string $path): Pattern
    {
        $wildcard = (substr($path, -1) !== '*') ? null : new Uri\PathWildcard();
        $path     = trim($path, '/*');

        $internalParams = '#(?:[^/]' . self::DELIM_LEFT . '|' . self::DELIM_RIGHT . '[^/])#';
        if (preg_match($internalParams, $path)) {
            $pattern = new Pattern\Regexp\RegexpPath($path, $this->getParams($path));
            return $wildcard ? new CompositePattern([$pattern, $wildcard]) : $pattern;
        }

        $segments = $path ? explode('/', $path) : [];
        $patterns = [];
        foreach ($segments as $segment) {
            $patterns[] = $this->pathSegment($segment);
        }

        if ($wildcard) {
            $patterns[] = $wildcard;
        }

        return count($patterns) === 1 ? $patterns[0] : new Pattern\CompositePattern($patterns);
    }

    private function pathSegment(string $segment): Pattern
    {
        if ($segment[0] !== self::DELIM_LEFT) {
            return new Uri\PathSegment($segment);
        }

        $id = substr($segment, 1, -1);
        return new Uri\PathRegexpSegment($id, $this->regexp[$id] ?? self::TYPE_REGEXP[self::TYPE_NUMBER]);
    }

    private function queryPattern(string $query): Pattern
    {
        return strpos($query, self::DELIM_LEFT)
            ? new Pattern\Regexp\RegexpQuery($query, $this->getParams($query))
            : Uri\Query::fromQueryString($query);
    }

    private function getParams(string $pattern): array
    {
        $params = [];
        foreach ($this->regexp as $name => $value) {
            $token = self::DELIM_LEFT . $name . self::DELIM_RIGHT;
            if (strpos($pattern, $token) !== false) {
                $params[$name] = $value;
            }
        }
        return $params;
    }

    private static function parseTypedParams(string &$uri, array &$params): void
    {
        if (strpos($uri, self::DELIM_LEFT) === false) { return; }

        $types     = array_keys(self::TYPE_REGEXP);
        $idPattern = '(?P<type>[' . preg_quote(implode('', $types), '/') . '])(?P<id>[a-zA-Z]+)';
        $regexp    = '/' . self::DELIM_LEFT . $idPattern . self::DELIM_RIGHT . '/';

        preg_match_all($regexp, $uri, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $params[$match['id']] = self::TYPE_REGEXP[$match['type']];
        }

        $replace = array_map(function ($type) { return self::DELIM_LEFT . $type; }, $types);
        $uri     = str_replace($replace, self::DELIM_LEFT, $uri);
    }

    private function resolvedPattern(): Pattern
    {
        $patterns = [];
        foreach ($this->uri as $name => $value) {
            if (!$pattern = $this->resolveUriPart($name, $value)) { continue; }
            $patterns[] = $pattern;
        }

        return count($patterns) === 1 ? $patterns[0] : new CompositePattern($patterns);
    }
}
