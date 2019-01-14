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

use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\PatternGate;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\Path;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Route\Splitter\RouteScan;
use Polymorphine\Routing\Route\Endpoint\NullEndpoint;


class ResourceSwitchBuilder implements Builder
{
    use CompositeBuilderMethods;

    protected $idName   = 'resource.id';
    protected $idRegexp = '[1-9][0-9]*';

    public function __construct(?BuilderContext $context = null, array $routes = [])
    {
        $this->context = $context ?? new BuilderContext();
        $this->routes  = $routes;
    }

    public function id(string $name, string $regexp = null): self
    {
        $this->idName = $name;
        return $regexp ? $this->withIdRegexp($regexp) : $this;
    }

    public function index(): ContextRouteBuilder
    {
        return $this->addBuilder('INDEX');
    }

    public function get(): ContextRouteBuilder
    {
        return $this->addBuilder('GET');
    }

    public function post(): ContextRouteBuilder
    {
        return $this->addBuilder('POST');
    }

    public function delete(): ContextRouteBuilder
    {
        return $this->addBuilder('DELETE');
    }

    public function patch(): ContextRouteBuilder
    {
        return $this->addBuilder('PATCH');
    }

    public function put(): ContextRouteBuilder
    {
        return $this->addBuilder('PUT');
    }

    public function add(): ContextRouteBuilder
    {
        return $this->addBuilder('NEW');
    }

    public function edit(): ContextRouteBuilder
    {
        return $this->addBuilder('EDIT');
    }

    protected function router(array $routes): Route
    {
        foreach ($routes as $name => &$route) {
            $route = $this->wrapRouteType($name, $route);
        }

        return $this->composeLogicStructure($routes);
    }

    protected function withIdRegexp(string $regexp)
    {
        if (preg_match('#' . $regexp . '#', 'new')) {
            $message = 'Uri keyword conflict: `resource/new/form` matches `resource/{id}/form` path';
            throw new Exception\BuilderLogicException($message);
        }

        $this->idRegexp = $regexp;
        return $this;
    }

    protected function formsRoute(array $routes): array
    {
        if (!isset($routes['EDIT']) && !isset($routes['NEW'])) { return []; }

        $forms = new RouteScan([
            'edit' => $this->pullRoute('EDIT', $routes),
            'new'  => $this->pullRoute('NEW', $routes)
        ]);

        return ['form' => new Route\Gate\UriAttributeSelect($forms, $this->idName, 'edit', 'new')];
    }

    private function wrapRouteType(string $name, Route $route): Route
    {
        switch ($name) {
            case 'INDEX':
            case 'POST':
                return new Route\Gate\PathEndGate($route);
            case 'NEW':
                return new PatternGate(new Path('new/form'), $route);
            case 'EDIT':
                $route = new PatternGate(new Path('form'), $route);
                break;
        }
        return new PatternGate(new PathSegment($this->idName, $this->idRegexp), $route);
    }

    private function composeLogicStructure(array $routes): Route
    {
        $getRoutes = $this->formsRoute($routes) + [
            'item'  => $this->pullRoute('GET', $routes),
            'index' => $this->pullRoute('INDEX', $routes)
        ];

        $routes['GET'] = new RouteScan($getRoutes);

        return new Route\Gate\UriAttributeSelect(new MethodSwitch($routes, 'GET'), $this->idName, 'item', 'index');
    }

    private function pullRoute(string $name, array &$routes): ?Route
    {
        $route = $routes[$name] ?? $this->wrapRouteType($name, new NullEndpoint());
        unset($routes[$name]);
        return $route;
    }
}
