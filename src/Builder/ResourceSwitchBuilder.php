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

    public function __construct(?RouteBuilder $context = null, array $routes = [])
    {
        parent::__construct($context, $routes + ['GET' => null, 'INDEX' => null, 'NEW' => null, 'EDIT' => null]);
    }

    public function id(string $name, string $regexp = null)
    {
        $this->idName = $name;
        return $regexp ? $this->withIdRegexp($regexp) : $this;
    }

    public function index(): RouteBuilder
    {
        return $this->addBuilder('INDEX');
    }

    public function get(): RouteBuilder
    {
        return $this->addBuilder('GET');
    }

    public function post(): RouteBuilder
    {
        return $this->addBuilder('POST');
    }

    public function delete(): RouteBuilder
    {
        return $this->addBuilder('DELETE');
    }

    public function patch(): RouteBuilder
    {
        return $this->addBuilder('PATCH');
    }

    public function put(): RouteBuilder
    {
        return $this->addBuilder('PUT');
    }

    public function add(): RouteBuilder
    {
        return $this->addBuilder('NEW');
    }

    public function edit(): RouteBuilder
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
                return new PatternGate(new Path('new/form'), $route);
            case 'EDIT':
                $route = new PatternGate(new Path('form'), $route);
                break;
        }
        return new PatternGate(new PathSegment($this->idName, $this->idRegexp), $route);
    }

    private function composeLogicStructure(array $routes): Route
    {
        $formRoutes = new ResponseScanSwitch([
            'edit' => $this->pullRoute('EDIT', $routes),
            'new'  => $this->pullRoute('NEW', $routes)
        ]);

        $routes['GET'] = new ResponseScanSwitch([
            'form'  => new Route\Gate\UriAttributeSelect($formRoutes, $this->idName, 'edit', 'new'),
            'item'  => $routes['GET'],
            'index' => $this->pullRoute('INDEX', $routes)
        ]);

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
        if (preg_match('#' . $regexp . '#', 'new')) {
            throw new Exception\BuilderLogicException('Uri conflict: `resource/new` matches `resource/{id}` path');
        }

        $this->idRegexp = $regexp;
        return $this;
    }
}
