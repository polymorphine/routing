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
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Static pattern matching and creating URI with specified path.
 */
class Path implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\PathContextMethods;

    protected $path;
    protected $relative = true;
    protected $fragment = false;

    /**
     * Path pattern may be full path required within request URI
     * or part of it. When leading slash is omitted path is matched
     * and created relatively to current processing state in routing
     * structure processing and pattern ending with an asterisk will
     * indicate that more path segments may exist.
     *
     * @example new Path('some/relative*')
     *          Will append given pattern to current URI prototype, and compare
     *          it against remaining (unprocessed) beginning of request's URI
     *          path.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $this->parsePath($path);
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        if (!$this->path) {
            return ($this->relativePath($request) === '') ? $request : null;
        }

        $requestPath = ($this->relative) ? $this->relativePath($request) : $request->getUri()->getPath();
        if (!$this->fragment) {
            if ($this->path !== $requestPath) { return null; }
            return $request->withAttribute(Route::PATH_ATTRIBUTE, '');
        }

        if (strpos($requestPath, $this->path) !== 0) { return null; }
        return $request->withAttribute(Route::PATH_ATTRIBUTE, $this->newPathContext($requestPath, $this->path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if (!$this->path) { return $prototype; }

        $prototypePath = $prototype->getPath();
        if ($this->relative) {
            return $prototype->withPath($prototypePath . '/' . $this->path);
        }

        $this->checkConflict(substr($this->path, 0, strlen($prototypePath)), $prototypePath);
        return $prototype->withPath($this->path);
    }

    private function checkConflict(string $routeSegment, string $prototypeSegment)
    {
        if ($prototypeSegment && $routeSegment !== $prototypeSegment) {
            $message = 'Uri conflict in `%s` prototype segment for `%s` uri';
            throw new Exception\UnreachableEndpointException(sprintf($message, $prototypeSegment, $this->path));
        }
    }

    private function parsePath(string $pattern): string
    {
        if (empty($pattern)) { return $pattern; }

        $this->relative = ($pattern[0] !== '/');
        $this->fragment = (substr($pattern, -1) === '*');

        return ($this->fragment) ? rtrim($pattern, '*') : $pattern;
    }
}
