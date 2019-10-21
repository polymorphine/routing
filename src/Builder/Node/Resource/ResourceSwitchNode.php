<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Node\Resource;

use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Builder\Exception;
use Polymorphine\Routing\Builder\Node\RouteNode;
use Polymorphine\Routing\Builder\Node\CompositeBuilderMethods;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\PatternGate;
use Polymorphine\Routing\Route\Gate\Pattern\CompositePattern;
use Polymorphine\Routing\Route\Gate\Pattern\UriPart\PathSegment as Path;
use Polymorphine\Routing\Route\Gate\Pattern\UriPart\PathRegexpSegment;
use Polymorphine\Routing\Route\Gate\UriAttributeSelect;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Route\Splitter\ScanSwitch;
use Polymorphine\Routing\Route\Endpoint\NullEndpoint;


/**
 * Builder node setting up REST resource routing structure for
 * http methods and (optionally) pseudo methods linking to
 * resource index and add/edit forms.
 */
class ResourceSwitchNode implements Node
{
    use CompositeBuilderMethods;

    protected $idName   = 'resource.id';
    protected $idRegexp = '[1-9][0-9]*';

    /**
     * Possible route names are: GET|POST|PUT|PATCH|DELETE and INDEX|ADD|EDIT.
     *
     * @param Context $context
     * @param array   $routes  associative array of http methods or
     */
    public function __construct(Context $context, array $routes = [])
    {
        $this->context = $context;
        $this->routes  = $routes;
    }

    /**
     * Setting resource id format and attribute name it's assigned to
     * (default: resource.id => [1-9][0-9]*).
     *
     * @param string      $name
     * @param null|string $regexp
     *
     * @return ResourceSwitchNode
     */
    public function id(string $name, string $regexp = null): self
    {
        $this->idName = $name;
        return $regexp ? $this->withIdRegexp($regexp) : $this;
    }

    /**
     * Creates route for resource list - GET method without specified id.
     *
     * @return RouteNode
     */
    public function index(): RouteNode
    {
        return $this->addBuilder('INDEX');
    }

    /**
     * Creates route for GET method of resource with given id.
     *
     * @return RouteNode
     */
    public function get(): RouteNode
    {
        return $this->addBuilder('GET');
    }

    /**
     * Creates endpoint for creating new resource - POST method
     * without given id.
     *
     * @return RouteNode
     */
    public function post(): RouteNode
    {
        return $this->addBuilder('POST');
    }

    /**
     * Creates endpoint with DELETE method removing resource with
     * given id.
     *
     * @return RouteNode
     */
    public function delete(): RouteNode
    {
        return $this->addBuilder('DELETE');
    }

    /**
     * Creates endpoint modifying resource with given id using
     * PATCH method.
     *
     * @return RouteNode
     */
    public function patch(): RouteNode
    {
        return $this->addBuilder('PATCH');
    }

    /**
     * Creates endpoint adding the resource with specific id
     * using PUT method.
     *
     * @return RouteNode
     */
    public function put(): RouteNode
    {
        return $this->addBuilder('PUT');
    }

    /**
     * Creates endpoint rendering form layout/field list to
     * add new resource.
     *
     * @return RouteNode
     */
    public function add(): RouteNode
    {
        return $this->addBuilder('NEW');
    }

    /**
     * Creates endpoint rendering form layout/field list with
     * values of resource with given id that can be modified.
     *
     * @return RouteNode
     */
    public function edit(): RouteNode
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
            throw Exception\BuilderLogicException::uriPatternKeywordConflict($this->idName);
        }

        $this->idRegexp = $regexp;
        return $this;
    }

    protected function formsRoute(array &$routes): array
    {
        if (!isset($routes['EDIT']) && !isset($routes['NEW'])) { return []; }

        $forms = new ScanSwitch([
            'edit' => $this->pullRoute('EDIT', $routes),
            'new'  => $this->pullRoute('NEW', $routes)
        ]);

        return ['form' => new UriAttributeSelect($forms, $this->idName, 'edit', 'new')];
    }

    private function wrapRouteType(string $name, Route $route): Route
    {
        switch ($name) {
            case 'INDEX':
            case 'POST':
                return $route;
            case 'NEW':
                return new PatternGate(new CompositePattern([new Path('new'), new Path('form')]), $route);
            case 'EDIT':
                $route = new PatternGate(new Path('form'), $route);
                break;
        }
        return new PatternGate(new PathRegexpSegment($this->idName, $this->idRegexp), $route);
    }

    private function composeLogicStructure(array &$routes): Route
    {
        $getRoutes = $this->formsRoute($routes) + [
            'item'  => $this->pullRoute('GET', $routes),
            'index' => $this->pullRoute('INDEX', $routes)
        ];

        $routes['GET'] = new UriAttributeSelect(new ScanSwitch($getRoutes), $this->idName, 'item', 'index');

        return new MethodSwitch($routes, 'GET');
    }

    private function pullRoute(string $name, array &$routes): ?Route
    {
        $route = $routes[$name] ?? $this->wrapRouteType($name, new NullEndpoint());
        unset($routes[$name]);
        return $route;
    }
}
