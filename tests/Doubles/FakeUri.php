<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Doubles;

use Psr\Http\Message\UriInterface;


class FakeUri implements UriInterface
{
    protected $supportedSchemes = [
        'http'  => ['port' => 80],
        'https' => ['port' => 443]
    ];

    private $uri;

    private $scheme   = '';
    private $userInfo = '';
    private $host     = '';
    private $port;
    private $path     = '';
    private $query    = '';
    private $fragment = '';

    public function __construct(array $segments = [])
    {
        isset($segments['scheme']) and $this->scheme = $segments['scheme'];
        isset($segments['user']) and $this->userInfo = $segments['user'];
        isset($segments['pass']) and $this->userInfo .= ':' . $segments['pass'];
        isset($segments['host']) and $this->host = $segments['host'];
        isset($segments['port']) and $this->port = (int) $segments['port'];
        isset($segments['path']) and $this->path = $segments['path'];
        isset($segments['query']) and $this->query = $segments['query'];
        isset($segments['fragment']) and $this->fragment = $segments['fragment'];
    }

    public static function fromString($uri = '')
    {
        return new self(parse_url($uri));
    }

    public function __toString(): string
    {
        isset($this->uri) or $this->uri = $this->buildUriString();

        return $this->uri;
    }

    public function __clone()
    {
        unset($this->uri);
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort()
    {
        $default = $this->port && $this->scheme && $this->supportedSchemes[$this->scheme]['port'] === $this->port;

        return ($default) ? null : $this->port;
    }

    public function getAuthority(): string
    {
        if (!$this->host) { return ''; }

        $user = $this->userInfo ? $this->userInfo . '@' : '';
        $port = $this->getPort();

        return $user . $this->host . ($port ? ':' . $port : '');
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme($scheme): UriInterface
    {
        $clone = clone $this;
        $clone->scheme = $scheme;
        return $clone;
    }

    public function withUserInfo($user, $password = null): UriInterface
    {
        empty($password) or $password = ':' . $password;

        $clone = clone $this;
        $clone->userInfo = $user . $password;
        return $clone;
    }

    public function withHost($host): UriInterface
    {
        $clone = clone $this;
        $clone->host = $host;
        return $clone;
    }

    public function withPort($port): UriInterface
    {
        $clone = clone $this;
        $clone->port = is_null($port) ? null : $port;
        return $clone;
    }

    public function withPath($path): UriInterface
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    public function withQuery($query): UriInterface
    {
        $clone = clone $this;
        $clone->query = $query;
        return $clone;
    }

    public function withFragment($fragment): UriInterface
    {
        $clone = clone $this;
        $clone->fragment = $fragment;
        return $clone;
    }

    protected function buildUriString(): string
    {
        $uri = ($this->scheme) ? $this->scheme . ':' : '';
        $uri .= ($this->host) ? $this->authorityPath() : $this->filteredPath();
        if ($this->query) {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment) {
            $uri .= '#' . $this->fragment;
        }

        return $uri ?: '/';
    }

    private function authorityPath()
    {
        $authority = '//' . $this->getAuthority();
        if (!$this->path) { return $authority; }

        return ($this->path[0] === '/') ? $authority . $this->path : $authority . '/' . $this->path;
    }

    private function filteredPath()
    {
        if (empty($this->path)) { return ''; }

        return ($this->path[0] === '/') ? '/' . ltrim($this->path, '/') : $this->path;
    }
}
