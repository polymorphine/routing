<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern;

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface as Uri;


abstract class Regexp implements Route\Gate\Pattern
{
    use UriTemplatePlaceholder;

    protected $pattern;
    protected $params;

    /**
     * @param string $pattern URI pattern with placeholder names
     * @param array  $params  Name to regexp mapping
     */
    public function __construct(string $pattern, array $params)
    {
        $this->pattern = $pattern;
        $this->params  = $params;
    }

    abstract public function matchedRequest(Request $request): ?Request;

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

    protected function matchUriPart(string $uriPart, Request $request): ?Request
    {
        $match = preg_match($this->regexp(), $uriPart, $attributes);
        if (!$match) { return null; }

        foreach (array_intersect_key($attributes, $this->params) as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return $request;
    }

    abstract protected function replacePlaceholders(Uri $uri, array $placeholders): Uri;

    private function regexp(): string
    {
        $regexp = preg_quote($this->pattern);
        foreach ($this->params as $name => $paramRegexp) {
            $placeholder = '\\' . self::DELIM_LEFT . $name . '\\' . self::DELIM_RIGHT;
            $replace     = '(?P<' . $name . '>' . $paramRegexp . ')';
            $regexp      = str_replace($placeholder, $replace, $regexp);
        }

        return '#^' . $regexp . '$#';
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
}
