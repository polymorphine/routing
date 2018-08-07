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
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\Path;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Route\Splitter\ResponseScanSwitch;
use Polymorphine\Routing\Exception\BuilderCallException;
use InvalidArgumentException;


class ResourceSwitchBuilder extends SwitchBuilder
{
    protected $methods = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'INDEX'];

    private $idName   = 'resource.id';
    private $idRegexp = '[1-9][0-9]*';

    public function id(string $name, string $regexp = null)
    {
        if (!empty($this->builders)) {
            throw new BuilderCallException('Cannot change id if routes were added');
        }

        $this->idName = $name;
        if ($regexp) {
            $this->idRegexp = $regexp;
        }
        return $this;
    }

    public function route(string $name = null): RouteBuilder
    {
        if (!$name) {
            throw new InvalidArgumentException('Http method or `INDEX` is required as name for resource route switch');
        }

        $builder = $this->addBuilder($this->context->route(), $this->validMethod($name));
        return ($name === 'INDEX' || $name === 'POST')
            ? $builder->pattern(new Path(''))
            : $builder->pattern(new PathSegment($this->idName, $this->idRegexp));
    }

    protected function router(array $routes): Route
    {
        $routes = $this->resolveIndexMethod($routes);

        return new MethodSwitch($routes);
    }

    protected function validMethod(string $method): string
    {
        if (in_array($method, $this->methods, true)) { return $method; }

        $message = 'Unknown http method `%s` for resource route switch';
        throw new InvalidArgumentException(sprintf($message, $method));
    }

    private function resolveIndexMethod(array $routes): array
    {
        if (!isset($routes['INDEX'])) { return $routes; }
        $routes['GET'] = isset($routes['GET'])
            ? new ResponseScanSwitch(['item' => $routes['GET']], $routes['INDEX'])
            : $routes['INDEX'];

        unset($routes['INDEX']);

        return $routes;
    }
}
