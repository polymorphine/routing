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
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UriInterface;


class MethodGate implements Route
{
    private const METHOD_SEPARATOR = '|';

    private $methods;
    private $route;

    /**
     * @param string $methods single http method or pipe separated method set (example: 'GET|POST|DELETE')
     * @param Route  $route
     */
    public function __construct(string $methods, Route $route)
    {
        $this->methods = explode(static::METHOD_SEPARATOR, $methods);
        $this->route   = $route;
    }

    public function forward(Request $request, Response $prototype): Response
    {
        $method = $request->getMethod();
        if ($method === 'OPTIONS') {
            return $this->options($request, $prototype);
        }

        return $this->isAllowed($method) ? $this->route->forward($request, $prototype) : $prototype;
    }

    public function select(string $path): Route
    {
        return $this->route->select($path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->route->uri($prototype, $params);
    }

    private function options(Request $request, Response $prototype): Response
    {
        if ($this->isAllowed('OPTIONS')) {
            return $this->route->forward($request->withoutAttribute(self::METHODS_ATTRIBUTE), $prototype);
        }

        $methods = array_intersect($request->getAttribute(self::METHODS_ATTRIBUTE, []), $this->methods);
        if (!$methods) { return $prototype; }

        $request = $request->withAttribute(self::METHODS_ATTRIBUTE, $methods);
        return $this->route->forward($request, $prototype);
    }

    private function isAllowed(string $method): bool
    {
        return in_array($method, $this->methods, true);
    }
}
