<?php

/*
 * This file is part of Polymorphine/Routing package.
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
use Closure;


class PatternEndpoint implements Route
{
    use Route\Pattern\PatternSelection;
    use LockedGatewayMethod;

    private $method;
    private $callback;
    private $pattern;

    public function __construct(string $method, Pattern $pattern, Closure $callback)
    {
        $this->method   = $method;
        $this->callback = $callback;
        $this->pattern  = $pattern;
    }

    public static function post(string $path, Closure $callback, array $params = [])
    {
        return new self('POST', self::selectPattern($path, $params), $callback);
    }

    public static function get(string $path, Closure $callback, array $params = [])
    {
        return new self('GET', self::selectPattern($path, $params), $callback);
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return ($this->methodMatch($request) && $request = $this->pattern->matchedRequest($request))
            ? $this->callback->__invoke($request)
            : $prototype;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->pattern->uri($prototype, $params);
    }

    private function methodMatch(ServerRequestInterface $request): bool
    {
        return $this->method === $request->getMethod();
    }
}
