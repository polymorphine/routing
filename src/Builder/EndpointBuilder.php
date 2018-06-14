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

use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;


class EndpointBuilder
{
    use GateBuildMethods;

    private $name;
    private $collector;

    public function __construct(string $name, RoutingBuilder $collector)
    {
        $this->name      = $name;
        $this->collector = $collector;
    }

    public function callback(callable $callback): void
    {
        $this->collector->add($this->name, $this->wrapGates(new CallbackEndpoint($callback)));
    }
}
