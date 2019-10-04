<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern\UriPart;

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Pattern capturing unprocessed path.
 */
class PathWildcard implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\PathContextMethods;
    use Route\Gate\Pattern\UriTemplatePlaceholder;

    public function __construct()
    {
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $path = implode('/', $this->relativePath($request));
        return $request->withAttribute(Route::WILDCARD_ATTRIBUTE, $path)->withAttribute(Route::PATH_ATTRIBUTE, []);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $prototype;
    }

    public function templateUri(UriInterface $uri): UriInterface
    {
        return $uri->withPath($uri->getPath() . $this->placeholder('/*'));
    }
}
