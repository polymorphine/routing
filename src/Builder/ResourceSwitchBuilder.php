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
use Polymorphine\Routing\Route\Gate\PatternGate as Pattern;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\Path;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Route\Splitter\ResponseScanSwitch;
use InvalidArgumentException;


class ResourceSwitchBuilder extends SwitchBuilder
{
    protected $methods = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'INDEX'];

    private $idName   = 'resource.id';
    private $idRegexp = '[1-9][0-9]*';

    public function id(string $name, string $regexp = '[1-9][0-9]*')
    {
        $this->idName   = $name;
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

        $routes = $this->resolveIndexMethod($routes);

        return new MethodSwitch($routes);
    }

    protected function validMethod(string $method): string
    {
        if (in_array($method, $this->methods, true)) { return $method; }

        $message = 'Unknown http method `%s` for resource route switch';
        throw new InvalidArgumentException(sprintf($message, $method));
    }

    private function wrapRouteType(string $type, Route $route): Route
    {
        return ($type === 'INDEX' || $type === 'POST')
            ? new Pattern(new Path(''), $route)
            : new Pattern(new PathSegment($this->idName, $this->idRegexp), $route);
    }

    private function resolveIndexMethod(array $routes): array
    {
        if (!isset($routes['INDEX'])) { return $routes; }
        $routes['GET'] = isset($routes['GET'])
            ? new ResponseScanSwitch(['index' => $this->pullRoute('INDEX', $routes)], $routes['GET'])
            : $this->pullRoute('INDEX', $routes);

        return $routes;
    }

    private function pullRoute(string $name, array &$routes): Route
    {
        $route = $routes[$name] ?? null;
        unset($routes[$name]);
        return $route;
    }
}
