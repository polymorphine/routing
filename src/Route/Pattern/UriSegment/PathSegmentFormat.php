<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Pattern\UriSegment;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


abstract class PathSegmentFormat implements Route\Pattern
{
    use Route\Pattern\PathContextMethods;

    private $name;

    public function __construct(string $name = 'id')
    {
        $this->name = $name;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        [$id, $path] = $this->splitRelativePath($request);
        if (!$this->validFormat($id)) { return null; }

        return $request->withAttribute($this->name, $id)->withAttribute(Route::PATH_ATTRIBUTE, $path);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if (!$id = $params[$this->name] ?? null) {
            $message = sprintf('Missing id parameter for `%s` uri', (string) $prototype);
            throw new Exception\InvalidUriParamsException($message);
        }

        if (!$this->validFormat($id)) {
            $message = sprintf('Invalid id format for `%s` uri (numeric value expected)', (string) $prototype);
            throw new Exception\InvalidUriParamsException($message);
        }

        return $prototype->withPath($prototype->getPath() . '/' . $id);
    }

    abstract protected function validFormat($id): bool;
}
