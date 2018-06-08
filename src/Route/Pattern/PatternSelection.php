<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Pattern;


trait PatternSelection
{
    protected static function selectPattern($pattern, $params)
    {
        return strpos($pattern, DynamicTargetMask::PARAM_DELIM_RIGHT)
            ? new DynamicTargetMask($pattern, $params)
            : new StaticUriMask($pattern);
    }
}
