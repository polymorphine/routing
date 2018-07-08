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

    public function method(string $methods, Route\Pattern $pattern = null)
    {
        if (isset($pattern)) { $this->pattern($pattern); }
        $this->gates[] = function (Route $route) use ($methods) {
            return new MethodGate($methods, $route);
        };
        return $this;
    }

    public function pattern(Route\Pattern $pattern)
    {
        $this->gates[] = function (Route $route) use ($pattern) {
            return new PatternGate($pattern, $route);
        };
        return $this;
    }

    public function get(Route\Pattern $pattern = null)
    {
        return $this->method('GET', $pattern);
    }

    public function post(Route\Pattern $pattern = null)
    {
        return $this->method('POST', $pattern);
    }

    public function put(Route\Pattern $pattern = null)
    {
        return $this->method('PUT', $pattern);
    }

    public function patch(Route\Pattern $pattern = null)
    {
        return $this->method('PATCH', $pattern);
    }

    public function delete(Route\Pattern $pattern = null)
    {
        return $this->method('DELETE', $pattern);
    }

    public function head(Route\Pattern $pattern = null)
    {
        return $this->method('HEAD', $pattern);
    }

    public function options(Route\Pattern $pattern = null)
    {
        return $this->method('OPTIONS', $pattern);
    }

    private function wrapGates(Route $route): Route
    {
        foreach ($this->gates as $gate) {
            $route = $gate($route);
        }

        return $route;
    }
}
