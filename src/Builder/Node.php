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


interface Node
{
    /**
     * Creates Route from gathered builder data and subsequent
     * Routes definitions.
     *
     * @return Route
     */
    public function build(): Route;
}
