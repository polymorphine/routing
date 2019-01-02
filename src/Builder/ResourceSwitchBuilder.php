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

    private $forms;

    private $idName   = 'resource.id';
    private $idRegexp = '[1-9][0-9]*';

    public function __construct(
        ?BuilderContext $context = null,
        array $routes = [],
        ?ContextRouteBuilder $forms = null
    ) {
        $this->context = $context ?? new BuilderContext();
        $this->routes  = $routes + ['GET' => null, 'INDEX' => null, 'NEW' => null, 'EDIT' => null];
        $this->forms   = $forms;
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
            $route = $this->wrapRouteType($name, $route ?: new NullEndpoint());
        }

        return $this->composeLogicStructure($routes);
    }

    private function wrapRouteType(string $name, Route $route): Route
    {
        switch ($name) {
            case 'INDEX':
            case 'POST':
                return new PatternGate(new Path(''), $route);
            case 'NEW':
                return $this->forms ? $route : new PatternGate(new Path('new/form'), $route);
            case 'EDIT':
                $route = $this->forms ? $route : new PatternGate(new Path('form'), $route);
                break;
        }
        return new PatternGate(new PathSegment($this->idName, $this->idRegexp), $route);
    }

    private function composeLogicStructure(array $routes): Route
    {
        $formRoutes = new RouteScan([
            'edit' => $this->pullRoute('EDIT', $routes),
            'new'  => $this->pullRoute('NEW', $routes)
        ]);

        $getRoutes = [
            'form'  => new Route\Gate\UriAttributeSelect($formRoutes, $this->idName, 'edit', 'new'),
            'item'  => $routes['GET'],
            'index' => $this->pullRoute('INDEX', $routes)
        ];

        if ($this->forms) {
            $route = $this->pullRoute('form', $getRoutes);
            $this->forms->joinRoute($route);
        }

        $routes['GET'] = new RouteScan($getRoutes);

        return new Route\Gate\UriAttributeSelect(new MethodSwitch($routes, 'GET'), $this->idName, 'item', 'index');
    }

    private function pullRoute(string $name, array &$routes): ?Route
    {
        $route = $routes[$name] ?? null;
        unset($routes[$name]);
        return $route;
    }

    private function withIdRegexp(string $regexp)
    {
        if (!$this->forms && preg_match('#' . $regexp . '#', 'new')) {
            throw new Exception\BuilderLogicException('Uri conflict: `resource/new` matches `resource/{id}` path');
        }

        $this->idRegexp = $regexp;
        return $this;
    }
}
