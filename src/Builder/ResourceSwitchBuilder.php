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


class ResourceSwitchBuilder extends SwitchBuilder
{
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

    public function index(): RouteBuilder
    {
        return $this->route('INDEX');
    }

    public function get(): RouteBuilder
    {
        return $this->route('GET');
    }

    public function post(): RouteBuilder
    {
        return $this->route('POST');
    }

    public function delete(): RouteBuilder
    {
        return $this->route('DELETE');
    }

    public function patch(): RouteBuilder
    {
        return $this->route('PATCH');
    }

    public function put(): RouteBuilder
    {
        return $this->route('PUT');
    }

    public function add(): RouteBuilder
    {
        return $this->route('NEW');
    }

    public function edit(): RouteBuilder
    {
        return $this->route('EDIT');
    }

    protected function router(array $routes): Route
    {
        $routes['GET']   = $routes['GET'] ?? new NullEndpoint();
        $routes['INDEX'] = $routes['INDEX'] ?? new NullEndpoint();

        foreach ($routes as $name => &$route) {
            $route = $this->wrapRouteType($name, $route);
        }

        $routes = $this->groupPseudoRoutes($routes);

        return new Route\Gate\ResourceGateway($this->idName, new MethodSwitch($routes, 'GET'));
    }

    private function route(string $name): RouteBuilder
    {
        return $this->addBuilder($this->context->route(), $name);
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

    private function groupPseudoRoutes(array $routes): array
    {
        $getMethodRoutes = array_filter([
            'edit'  => $this->pullRoute('EDIT', $routes),
            'item'  => $routes['GET'],
            'index' => $this->pullRoute('INDEX', $routes),
            'new'   => $this->pullRoute('NEW', $routes)
        ]);
        $routes['GET'] = new ResponseScanSwitch($getMethodRoutes);

        return $routes;
    }

    private function pullRoute(string $name, array &$routes): ?Route
    {
        $route = $routes[$name] ?? null;
        unset($routes[$name]);
        return $route;
    }
}
