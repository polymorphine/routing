<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\MethodGate;
use Polymorphine\Routing\Route\Gate\PatternGate;


trait GateBuildMethods
{
    use Route\Pattern\PatternSelection;

    private $gates = [];

    public function method(string $methods, string $pattern = null, array $params = [])
    {
        if (isset($pattern)) { $this->pattern($pattern, $params); }
        $this->gates[] = function (Route $route) use ($methods) {
            return new MethodGate($methods, $route);
        };
        return $this;
    }

    public function pattern(string $uriPattern, array $params = [])
    {
        $pattern       = self::selectPattern($uriPattern, $params);
        $this->gates[] = function (Route $route) use ($pattern) {
            return new PatternGate($pattern, $route);
        };
        return $this;
    }

    public function get(string $pattern = null, array $params = [])
    {
        return $this->method('GET', $pattern, $params);
    }

    public function post(string $pattern = null, array $params = [])
    {
        return $this->method('POST', $pattern, $params);
    }

    public function put(string $pattern = null, array $params = [])
    {
        return $this->method('PUT', $pattern, $params);
    }

    public function patch(string $pattern = null, array $params = [])
    {
        return $this->method('PATCH', $pattern, $params);
    }

    public function delete(string $pattern = null, array $params = [])
    {
        return $this->method('DELETE', $pattern, $params);
    }

    public function head(string $pattern = null, array $params = [])
    {
        return $this->method('HEAD', $pattern, $params);
    }

    public function options(string $pattern = null, array $params = [])
    {
        return $this->method('OPTIONS', $pattern, $params);
    }

    private function wrapGates(Route $route): Route
    {
        foreach ($this->gates as $gate) {
            $route = $gate($route);
        }

        return $route;
    }
}
