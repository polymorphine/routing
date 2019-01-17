<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Resource;

use Polymorphine\Routing\Builder\BuilderContext;
use Polymorphine\Routing\Builder\ContextRouteBuilder;
use Polymorphine\Routing\Builder\RouteScanBuilder;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Gate\PathEndGate;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\PathSegment;


class ContextFormsResourceSwitchBuilder extends ResourceSwitchBuilder
{
    private $forms;
    private $formSwitch;

    public function __construct(
        FormsContext $forms,
        ?BuilderContext $context = null,
        array $routes = []
    ) {
        $this->forms = $forms;
        parent::__construct($context, $this->removeFormRoutes($routes));
    }

    public function add(): ContextRouteBuilder
    {
        return $this->formSwitch()->route('new')->wrapRouteCallback(function (Route $route) {
            return new PathEndGate($route);
        });
    }

    public function edit(): ContextRouteBuilder
    {
        $idPattern = new PathSegment($this->idName, $this->idRegexp);
        return $this->formSwitch()->route('edit')->pattern($idPattern);
    }

    protected function withIdRegexp(string $regexp)
    {
        $this->idRegexp = $regexp;
        return $this;
    }

    protected function formsRoute(array $routes): array
    {
        return [];
    }

    private function formSwitch(): RouteScanBuilder
    {
        return $this->formSwitch ?: $this->formSwitch = $this->forms->formSwitch($this->idName);
    }

    private function removeFormRoutes(array $routes): array
    {
        foreach (['NEW' => 'add', 'EDIT' => 'edit'] as $formRoute => $method) {
            if (!isset($routes[$formRoute])) { continue; }
            $this->{$method}()->joinRoute($routes[$formRoute]);
            unset($routes[$formRoute]);
        }

        return $routes;
    }
}
