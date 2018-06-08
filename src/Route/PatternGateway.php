<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route;

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class PatternGateway implements Route
{
    use Route\Pattern\PatternSelection;

    private $pattern;
    private $route;

    public function __construct(Pattern $pattern, Route $route)
    {
        $this->pattern = $pattern;
        $this->route   = $route;
    }

    public static function withPatternString(string $pattern, Route $route, array $params = [])
    {
        return new self(self::selectPattern($pattern, $params), $route);
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $notFound): ResponseInterface
    {
        $request = $this->pattern->matchedRequest($request);

        return $request ? $this->route->forward($request, $notFound) : $notFound;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $uri = $this->route->uri($prototype, $params);

        return $this->pattern->uri($uri, $params);
    }

    public function gateway(string $path): Route
    {
        return new self($this->pattern, $this->route->gateway($path));
    }
}
