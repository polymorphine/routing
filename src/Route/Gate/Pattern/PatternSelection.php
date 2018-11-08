<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern;

use Polymorphine\Routing\Route\Gate\Pattern;


trait PatternSelection
{
    protected static function selectPattern($pattern, $params)
    {
        return strpos($pattern, Pattern::DELIM_RIGHT)
            ? new DynamicTargetMask($pattern, $params)
            : UriPattern::fromUriString($pattern);
    }
}
