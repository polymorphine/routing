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
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment as Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;


class UriPattern implements Pattern
{
    private $uri;
    private $pattern;

    public function __construct(array $segments)
    {
        $this->uri = $segments;
    }

    public static function fromUriString(string $uri)
    {
        if (!$segments = parse_url($uri)) {
            throw new InvalidArgumentException(sprintf('Malformed URI string: `%s`', $uri));
        }

        return new self($segments);
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
                return new Uri\Path($value);
            case 'query':
                return new Uri\Query($value);
        }

        return null;
    }
}
