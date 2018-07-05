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


class PathNumericId implements Route\Pattern
{
    use Route\Pattern\PathContextMethods;

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        [$id, $path] = $this->splitRelativePath($request);
        return is_numeric($id) ? $request->withAttribute('id', $id)->withAttribute(Route::PATH_ATTRIBUTE, $path) : null;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if (!$id = $params['id'] ?? null) {
            throw new Exception\InvalidUriParamsException('Missing id');
        }

        if (!is_numeric($id)) {
            throw new Exception\InvalidUriParamsException('Invalid id');
        }

        return $prototype->withPath($prototype->getPath() . '/' . $id);
    }
}
