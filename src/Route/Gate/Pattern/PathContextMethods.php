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

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;


trait PathContextMethods
{
    private function relativePath(ServerRequestInterface $request): string
    {
        return $request->getAttribute(Route::PATH_ATTRIBUTE) ?? ltrim($request->getUri()->getPath(), '/');
    }

    private function isPathFullyMatched(ServerRequestInterface $request): bool
    {
        return !$this->relativePath($request) || $request->getAttribute(self::WILDCARD_ATTRIBUTE);
    }

    private function splitRelativePath(ServerRequestInterface $request): array
    {
        return explode('/', ltrim($this->relativePath($request), '/'), 2) + [null, ''];
    }
}
