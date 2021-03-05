<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Doubles;

use Polymorphine\Routing\Builder\MappedRoutes;


class MockedMappedRoutes extends MappedRoutes
{
    public bool $modified = false;

    public function withRouterCallback(callable $router): MappedRoutes
    {
        $this->modified = true;
        return parent::withRouterCallback($router);
    }
}
