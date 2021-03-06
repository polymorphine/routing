<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Node\Resource;

use Polymorphine\Routing\Builder\Context;
use Polymorphine\Routing\Builder\Node\RouteNode;
use Polymorphine\Routing\Builder\Node\ScanSwitchNode;
use Polymorphine\Routing\Route\Gate\Pattern\UriPart\PathRegexpSegment;


/**
 * Extension to ResourceSwitchNode that manages form handling paths
 * in separate context (accessed with different URI path).
 */
class LinkedFormsResourceSwitchNode extends ResourceSwitchNode
{
    private FormsContext   $forms;
    private ScanSwitchNode $formSwitch;

    public function __construct(
        FormsContext $forms,
        Context $context,
        array $routes = []
    ) {
        $this->forms = $forms;
        parent::__construct($context, $this->removeFormRoutes($routes));
    }

    public function add(): RouteNode
    {
        return $this->formSwitch()->route('new');
    }

    public function edit(): RouteNode
    {
        $idPattern = new PathRegexpSegment($this->idName, $this->idRegexp);
        return $this->formSwitch()->route('edit')->pattern($idPattern);
    }

    protected function withIdRegexp(string $regexp)
    {
        $this->idRegexp = $regexp;
        return $this;
    }

    protected function formsRoute(array &$routes): array
    {
        return [];
    }

    private function formSwitch(): ScanSwitchNode
    {
        return $this->formSwitch ??= $this->forms->builder($this->idName);
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
