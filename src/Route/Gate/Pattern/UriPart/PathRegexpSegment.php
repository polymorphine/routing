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
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Dynamic pattern constraint with value capturing and build
 * directive for single path segment.
 */
class PathRegexpSegment implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\PathContextMethods;
    use Route\Gate\Pattern\UriTemplatePlaceholder;

    private $name;
    private $regexp;

    /**
     * URI path segment will be built using given $name param, and
     * matched capturing ServerRequestInterface attribute with its
     * name relative to current processing stage in routing tree.
     *
     * Regexp will be used in both matching and building stages throwing
     * InvalidUriParamsException in case of missing param to build URI
     * or param value that would produce URI that couldn't be matched.
     *
     * @param string $name
     * @param string $regexp
     */
    public function __construct(string $name = 'id', string $regexp = self::TYPE_REGEXP[self::TYPE_NUMBER])
    {
        $this->name   = $name;
        $this->regexp = $regexp;
    }

    public static function numeric(string $name = 'id')
    {
        return new static($name, self::TYPE_REGEXP[self::TYPE_NUMERIC]);
    }

    public static function number(string $name = 'id')
    {
        return new static($name);
    }

    public static function slug(string $name = 'slug')
    {
        return new static($name, self::TYPE_REGEXP[self::TYPE_SLUG]);
    }

    public static function name(string $name = 'name')
    {
        return new static($name, self::TYPE_REGEXP[self::TYPE_NAME]);
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $segment = $this->pathSegment($request);

        return $this->validFormat($segment)
            ? $this->newContextRequest($request->withAttribute($this->name, $segment))
            : null;
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

    public function templateUri(UriInterface $uri): UriInterface
    {
        $presetType = array_search($this->regexp, self::TYPE_REGEXP, true);
        $definition = $presetType ? $presetType . $this->name : $this->name . ':' . $this->regexp;
        return $uri->withPath($uri->getPath() . '/' . $this->placeholder($definition));
    }

    protected function validFormat($id): bool
    {
        return (bool) preg_match('#^' . $this->regexp . '$#', (string) $id);
    }
}
