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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class PatternGate implements Route
{
    use Route\Pattern\PatternSelection;

    private $pattern;
    private $route;

    public function __construct(Route\Pattern $pattern, Route $route)
    {
        $this->pattern = $pattern;
        $this->route   = $route;
    }

    public static function withPatternString(string $pattern, Route $route, array $params = [])
    {
        return new self(self::selectPattern($pattern, $params), $route);
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
}
