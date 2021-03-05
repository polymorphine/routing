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

use Psr\Container\ContainerInterface;


class FakeContainer implements ContainerInterface
{
    public array $records = [];

    public function __construct(array $records = [])
    {
        $this->records = $records;
    }

    public function get($id)
    {
        return $this->records[$id] ?? null;
    }

    public function has($id)
    {
        return isset($this->records[$id]);
    }
}
