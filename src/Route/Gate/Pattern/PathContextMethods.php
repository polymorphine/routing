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
    private function pathSegment(ServerRequestInterface $request): ?string
    {
        $segments = $this->relativePath($request);
        return $segments[0] ?? null;
    }

    private function newContextRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $segments = $this->relativePath($request);
        array_shift($segments);
        return $request->withAttribute(Route::PATH_ATTRIBUTE, $segments);
    }

    private function relativePath(ServerRequestInterface $request): array
    {
        return $request->getAttribute(Route::PATH_ATTRIBUTE) ?? $this->readPathSegments($request);
    }

    private function readPathSegments(ServerRequestInterface $request): array
    {
        $path = ltrim($request->getUri()->getPath(), '/');
        return $path ? explode('/', $path) : [];
    }
}
