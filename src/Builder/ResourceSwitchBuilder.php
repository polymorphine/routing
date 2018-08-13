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
use Polymorphine\Routing\Route\Gate\PatternGate;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\Path;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Route\Splitter\ResponseScanSwitch;
use Polymorphine\Routing\Route\Endpoint\NullEndpoint;
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
            throw new Exception\BuilderLogicException('Uri conflict: NEW pseudo method uri matches id regexp');
        }

        $this->idRegexp = $regexp;
        return $this;
    }

    public function route(string $name): RouteBuilder
    {
        if (!$name) {
            $message = 'Route name is required as name for resource switch (allowed values `%s`)';
            throw new InvalidArgumentException(sprintf($message, implode('`,`', $this->methods)));
        }
        return $this->addBuilder($this->context->route(), $this->validMethod($name));
    }

    protected function router(array $routes): Route
    {
        $routes['GET']   = $routes['GET'] ?? new NullEndpoint();
        $routes['INDEX'] = $routes['INDEX'] ?? new NullEndpoint();

        foreach ($routes as $name => &$route) {
            $route = $this->wrapRouteType($name, $route);
        }

        $routes['GET'] = new ResponseScanSwitch($this->extractPseudoMethodRoutes($routes), $routes['GET']);

        return new Route\Gate\ResourceGateway($this->idName, new MethodSwitch($routes, 'GET'));
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
                return new PatternGate(new Path(''), $route);
            case 'NEW':
                return new PatternGate(new Path('new/form'), $route);
            case 'EDIT':
                $route = new PatternGate(new Path('form'), $route);
                break;
        }
        return new PatternGate(new PathSegment($this->idName, $this->idRegexp), $route);
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
