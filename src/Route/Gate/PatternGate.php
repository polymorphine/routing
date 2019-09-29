<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Map\Trace;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


/**
 * Gate Route forwarding request further on matching request URI
 * and producing URI based on provided Pattern.
 */
class PatternGate implements Route
{
    private $pattern;
    private $route;

    public function __construct(Pattern $pattern, Route $route)
    {
        $this->pattern = $pattern;
        $this->route   = $route;
    }

    public static function fromPatternString(string $uriPattern, Route $route, array $params = [])
    {
        $pattern = strpos($uriPattern, Pattern::DELIM_RIGHT)
            ? new Pattern\DynamicTargetMask($uriPattern, $params)
            : Pattern\UriPattern::fromUriString($uriPattern);

        return new self($pattern, $route);
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $request = $this->pattern->matchedRequest($request);

        return $request ? $this->route->forward($request, $prototype) : $prototype;
    }

    public function select(string $path): Route
    {
        return new self($this->pattern, $this->route->select($path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $prototype = $this->pattern->uri($prototype, $params);
        return $this->route->uri($prototype, $params);
    }

    public function routes(Trace $trace): void
    {
        $trace->withPattern($this->pattern)->follow($this->route);
    }
}
