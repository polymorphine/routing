<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern\Regexp;

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface as Uri;


class RegexpPath extends Route\Gate\Pattern\Regexp
{
    use Route\Gate\Pattern\PathContextMethods;

    public function matchedRequest(Request $request): ?Request
    {
        $pathSegments  = $this->relativePath($request);
        $patternLength = count(explode('/', $this->pattern));
        if ($patternLength > count($pathSegments)) { return null; }

        $path    = implode('/', array_slice($pathSegments, 0, $patternLength));
        $matched = $this->matchUriPart($path, $request);
        if (!$matched) { return null; }

        return $matched->withAttribute(Route::PATH_ATTRIBUTE, array_slice($pathSegments, $patternLength));
    }

    protected function replacePlaceholders(Uri $uri, array $placeholders): Uri
    {
        $path = str_replace(array_keys($placeholders), $placeholders, $this->pattern);
        return $uri->withPath($uri->getPath() . '/' . $path);
    }
}
