<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Builder\Node;

use Polymorphine\Routing\Builder\Node;
use Polymorphine\Routing\Builder\Exception;
use Polymorphine\Routing\Route;


/**
 * Builder node linking to another, possibly not built yet route.
 * Used to connect multiple (alternative) routes to same routing
 * path resolved by builder nodes when all definitions are not
 * yet established (router not built).
 *
 * @see RouteNode::link()
 * @see RouteNode::joinLink()
 */
class LinkedRouteNode implements Node
{
    private $futureRoute;

    /**
     * @param Route|null $futureRoute deferred Route build resolved by builder at
     *                                composition stage
     */
    public function __construct(?Route &$futureRoute)
    {
        $this->futureRoute = &$futureRoute;
    }

    public function build(): Route
    {
        if (!$this->futureRoute instanceof Route) {
            throw Exception\BuilderLogicException::unresolvedLinkedRoute();
        }

        return $this->futureRoute;
    }
}
