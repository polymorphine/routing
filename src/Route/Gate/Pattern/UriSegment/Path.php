<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern\UriSegment;

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Static pattern matching and creating URI with specified path.
 */
class Path implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\PathContextMethods;

    private $path;

    /**
     * Path pattern may be full path required within request URI
     * or part of it. Path is matched and created relatively to
     * current processing state in routing structure.
     *
     * @param string $path Full path or fragment without leading and trailing slashes
     */
    public function __construct(string $path)
    {
        $this->path = trim($path, '/');
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $requestPath = $this->relativePath($request);
        if (!$this->path) {
            return ($requestPath === $this->path) ? $request->withAttribute(Route::PATH_ATTRIBUTE, '') : null;
        }

        return (strpos($requestPath, $this->path) === 0)
            ? $request->withAttribute(Route::PATH_ATTRIBUTE, $this->newPathContext($requestPath, $this->path))
            : null;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->path ? $prototype->withPath($prototype->getPath() . '/' . $this->path) : $prototype;
    }
}
