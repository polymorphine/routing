<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate;

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Polymorphine\Routing\Exception;


class PathEndGate implements Route
{
    use Route\Gate\Pattern\PathContextMethods;

    private $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return $this->relativePath($request) ? $prototype : $this->route->forward($request, $prototype);
    }

    public function select(string $path): Route
    {
        return new self($this->route->select($path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $uri = $this->route->uri($prototype, $params);
        $this->checkRequiredPath($uri->getPath(), $prototype->getPath());

        return $uri;
    }

    private function checkRequiredPath(string $routePath, string $requiredPath)
    {
        if ($routePath !== $requiredPath) {
            $message = 'Required path is `%s`. Path built for this route is `%s`';
            throw new Exception\UnreachableEndpointException(sprintf($message, $requiredPath, $routePath));
        }
    }
}
