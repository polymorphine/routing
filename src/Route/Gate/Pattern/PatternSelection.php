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
    protected static function selectPattern(string $pattern, array $params = []): Pattern
    {
        return strpos($pattern, Pattern::DELIM_RIGHT)
            ? new DynamicTargetMask($pattern, $params)
            : UriPattern::fromUriString($pattern);
    }

    private function patternSegment(string $segment, array $regexp): ?Pattern
    {
        if (!$id = $this->patternId($segment)) { return null; }

        if (isset($regexp[$id])) {
            return new Pattern\UriSegment\PathSegment($id, $regexp[$id]);
        }

        [$type, $id] = [$id[0], substr($id, 1)];

        return isset(Pattern::TYPE_REGEXP[$type])
            ? new Pattern\UriSegment\PathSegment($id, Pattern::TYPE_REGEXP[$type])
            : null;
    }

    private function patternId(string $segment): ?string
    {
        if ($segment[0] !== Pattern::DELIM_LEFT) { return null; }
        $id = substr($segment, 1, -1);
        return ($segment === Pattern::DELIM_LEFT . $id . Pattern::DELIM_RIGHT) ? $id : null;
    }
}
