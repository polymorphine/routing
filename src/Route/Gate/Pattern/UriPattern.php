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

use Polymorphine\Routing\Route\Gate\Pattern;
use Polymorphine\Routing\Route\Gate\Pattern\UriPart as Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;


/**
 * Static Pattern resolved into composition of individual URI segment Patterns.
 */
class UriPattern implements Pattern
{
    private $uri;
    private $regexp;
    private $pattern;

    /**
     * @param array $segments associative array of URI segments as returned by parse_url() function
     * @param array $regexp   associative array of RegExp patterns for uri path segments
     */
    public function __construct(array $segments, array $regexp = [])
    {
        $this->uri    = $segments;
        $this->regexp = $regexp;
    }

    public static function fromUriString(string $uri, array $regexp = [])
    {
        if (strpos($uri, Pattern::DELIM_LEFT . '#') !== false) {
            return self::removeFragmentDelimiter($uri, $regexp);
        }

        if (!$segments = parse_url($uri)) {
            throw new InvalidArgumentException(sprintf('Malformed URI string: `%s`', $uri));
        }

        return new self($segments, $regexp);
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        isset($this->pattern) or $this->pattern = $this->parsePattern();
        return $this->pattern->matchedRequest($request);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        isset($this->pattern) or $this->pattern = $this->parsePattern();
        return $this->pattern->uri($prototype, $params);
    }

    private function parsePattern(): Pattern
    {
        $patterns = [];
        foreach ($this->uri as $name => $value) {
            if (!$pattern = $this->resolvePattern($name, $value)) { continue; }
            $patterns[] = $pattern;
        }

        return (count($patterns) === 1) ? $patterns[0] : new CompositePattern($patterns);
    }

    private function resolvePattern(string $name, $value): ?Pattern
    {
        if (!$value) { return null; }

        switch ($name) {
            case 'scheme':
                return new Uri\Scheme($value);
            case 'host':
                return ($value[0] === '*') ? new Uri\HostDomain(ltrim($value, '*')) : new Uri\Host($value);
            case 'port':
                return new Uri\Port($value);
            case 'user':
                $pass = isset($this->uri['pass']) ? ':' . $this->uri['pass'] : '';
                return new Uri\UserInfo($value . $pass);
            case 'path':
                return $this->pathPattern($value);
            case 'query':
                return new Uri\Query($value);
        }

        return null;
    }

    private function pathPattern(string $path): Pattern
    {
        $segments = explode('/', trim($path, '/*'));
        $patterns = [];
        foreach ($segments as $segment) {
            $patterns[] = $this->pathSegment($segment);
        }

        return count($patterns) === 1 ? $patterns[0] : new Pattern\CompositePattern($patterns);
    }

    private function pathSegment(string $segment): ?Pattern
    {
        if (!$id = $this->patternId($segment)) {
            return new Uri\PathSegment($segment);
        }

        if (isset($this->regexp[$id])) {
            return new Uri\PathRegexpSegment($id, $this->regexp[$id]);
        }

        [$type, $id] = [$id[0], substr($id, 1)];

        return isset(Pattern::TYPE_REGEXP[$type])
            ? new Uri\PathRegexpSegment($id, Pattern::TYPE_REGEXP[$type])
            : new Uri\PathRegexpSegment($type . $id);
    }

    private function patternId(string $segment): ?string
    {
        if ($segment[0] !== Pattern::DELIM_LEFT) { return null; }
        $id = substr($segment, 1, -1);
        return ($segment === Pattern::DELIM_LEFT . $id . Pattern::DELIM_RIGHT) ? $id : null;
    }

    private static function removeFragmentDelimiter(string $uri, array $regexp): self
    {
        $pattern = Pattern::DELIM_LEFT . '#([a-z]+)' . Pattern::DELIM_RIGHT;
        preg_match_all('/' . $pattern . '/', $uri, $matches);
        foreach ($matches[1] as $id) {
            $regexp[$id] = Pattern::TYPE_REGEXP['#'];
            $uri = str_replace(
                Pattern::DELIM_LEFT . '#' . $id . Pattern::DELIM_RIGHT,
                Pattern::DELIM_LEFT . $id . Pattern::DELIM_RIGHT,
                $uri
            );
        }

        return self::fromUriString($uri, $regexp);
    }
}
