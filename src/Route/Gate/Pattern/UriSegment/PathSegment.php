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


class PathSegment implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\PathContextMethods;

    private $name;
    private $regexp;

    public function __construct(string $name = 'id', string $regexp = '[1-9][0-9]*')
    {
        $this->name   = $name;
        $this->regexp = $regexp;
    }

    public static function numeric(string $name = 'id')
    {
        return new static($name, '[0-9]+');
    }

    public static function number(string $name = 'id')
    {
        return new static($name, '[1-9][0-9]*');
    }

    public static function slug(string $name = 'slug')
    {
        return new static($name, '[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]');
    }

    public static function name(string $name = 'name')
    {
        return new static($name, '[a-zA-Z0-9]+');
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
            $message = 'Missing id parameter for `%s` uri';
            throw new Exception\InvalidUriParamsException(sprintf($message, (string) $prototype));
        }

        if (!$this->validFormat($id)) {
            $message = 'Invalid id format for `%s` uri (expected pattern: `%s`)';
            throw new Exception\InvalidUriParamsException(sprintf($message, (string) $prototype, $this->regexp));
        }

        return $prototype->withPath($prototype->getPath() . '/' . $id);
    }

    protected function validFormat($id): bool
    {
        return (bool) preg_match('#^' . $this->regexp . '$#', (string) $id);
    }
}
