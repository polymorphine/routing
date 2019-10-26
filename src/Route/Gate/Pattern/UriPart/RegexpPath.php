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
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface as Uri;


class RegexpPath implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\UriTemplatePlaceholder;

    private $path;
    private $params;

    public function __construct(string $path, array $params)
    {
        $this->path   = $path;
        $this->params = $params;
    }

    public function matchedRequest(Request $request): ?Request
    {
        $path = $request->getUri()->getPath();

        $pattern = $this->patternRegexp();
        if (!preg_match($pattern, $path, $attributes)) { return null; }

        foreach (array_intersect_key($attributes, $this->params) as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return $request;
    }

    public function uri(Uri $prototype, array $params): Uri
    {
        $placeholders = [];
        foreach ($this->params as $name => $type) {
            $token = self::DELIM_LEFT . $name . self::DELIM_RIGHT;
            $placeholders[$token] = $this->validParam($name, $type, $params);
        }

        return $this->replacePlaceholders($prototype, $placeholders);
    }

    public function templateUri(Uri $uri): Uri
    {
        $placeholders = [];
        foreach ($this->params as $name => $type) {
            $token      = self::DELIM_LEFT . $name . self::DELIM_RIGHT;
            $presetType = array_search($type, self::TYPE_REGEXP, true);
            $definition = $presetType ? $presetType . $name : $name . ':' . $type;
            $placeholders[$token] = $this->placeholder($definition);
        }

        return $this->replacePlaceholders($uri, $placeholders);
    }

    private function patternRegexp()
    {
        $regexp = preg_quote($this->path);
        foreach ($this->params as $name => $paramRegexp) {
            $placeholder = '\\' . self::DELIM_LEFT . $name . '\\' . self::DELIM_RIGHT;
            $replace     = '(?P<' . $name . '>' . $paramRegexp . ')';
            $regexp      = str_replace($placeholder, $replace, $regexp);
        }

        if ($this->path[0] === '/') {
            $regexp = '^' . $regexp;
        }

        return '#' . $regexp . '$#';
    }

    private function validParam(string $name, string $type, array $params): string
    {
        if (!isset($params[$name])) {
            throw Route\Exception\InvalidUriParamException::missingParam($name);
        }

        $value = (string) $params[$name];
        if (!preg_match('/^' . $type . '$/', $value)) {
            throw Route\Exception\InvalidUriParamException::formatMismatch($name, $type);
        }

        return $value;
    }

    private function replacePlaceholders(Uri $uri, array $placeholders): Uri
    {
        $path = str_replace(array_keys($placeholders), $placeholders, $this->path);

        return $this->setPath($path, $uri);
    }

    private function setPath(string $path, Uri $prototype): Uri
    {
        $prototypePath = $prototype->getPath();
        if ($path[0] !== '/') {
            return $prototype->withPath($prototypePath . '/' . $path);
        }

        if ($prototypePath && strpos($path, $prototypePath) !== 0) {
            throw Route\Exception\InvalidUriPrototypeException::pathConflict($path, $prototype);
        }

        return $prototype->withPath($path);
    }
}
