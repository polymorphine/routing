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

use Polymorphine\Routing\Exception\BuilderCallException;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\PatternGate as Pattern;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\Path;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Route\Splitter\ResponseScanSwitch;
use InvalidArgumentException;


class ResourceSwitchBuilder extends SwitchBuilder
{
    private $methods       = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'INDEX', 'NEW', 'EDIT'];
    private $pseudoMethods = ['INDEX', 'NEW', 'EDIT'];

    private $idName   = 'resource.id';
    private $idRegexp = '[1-9][0-9]*';

    public function id(string $name, string $regexp = '[1-9][0-9]*')
    {
        $this->idName = $name;
        if (!$regexp) { return $this; }

        if (preg_match('#' . $regexp . '#', 'new')) {
            throw new BuilderCallException('Uri conflict: NEW pseudo method uri matches id regexp');
        }

        $this->idRegexp = $regexp;
        return $this;
    }

    public function route(string $name = null): RouteBuilder
    {
        if (!$name) {
            $message = 'Route name is required as name for resource switch (allowed values `%s`)';
            throw new InvalidArgumentException(sprintf($message, implode('`,`', $this->methods)));
        }
        return parent::route($this->validMethod($name));
    }

    protected function router(array $routes): Route
    {
        foreach ($routes as $name => &$route) {
            $route = $this->wrapRouteType($name, $route);
        }

        $routes = $this->resolvePseudoMethods($routes);

        return new MethodSwitch($routes);
    }

    protected function validMethod(string $method): string
    {
        if (in_array($method, $this->methods, true)) { return $method; }

        $message = 'Unknown http method `%s` for resource route switch';
        throw new InvalidArgumentException(sprintf($message, $method));
    }

    private function wrapRouteType(string $name, Route $route): Route
    {
        switch ($name) {
            case 'INDEX':
            case 'POST':
                return new Pattern(new Path(''), $route);
            case 'NEW':
                return new Pattern(new Path('new/form'), $route);
            case 'EDIT':
                $route = new Pattern(new Path('form'), $route);
                break;
        }
        return new Pattern(new PathSegment($this->idName, $this->idRegexp), $route);
    }

    private function resolvePseudoMethods(array $routes): array
    {
        if (!$pseudoMethodRoutes = $this->extractPseudoMethodRoutes($routes)) { return $routes; }
        $routes['GET'] = isset($routes['GET'])
            ? new ResponseScanSwitch($pseudoMethodRoutes, $routes['GET'])
            : new ResponseScanSwitch($pseudoMethodRoutes);

        return $routes;
    }

    private function extractPseudoMethodRoutes(array &$routes): array
    {
        $pseudoRoutes = [];
        foreach ($this->pseudoMethods as $name) {
            if (!isset($routes[$name])) { continue; }
            $pseudoRoutes[strtolower($name)] = $this->pullRoute($name, $routes);
        }
        return $pseudoRoutes;
    }

    private function pullRoute(string $name, array &$routes): Route
    {
        $route = $routes[$name] ?? null;
        unset($routes[$name]);
        return $route;
    }
}
