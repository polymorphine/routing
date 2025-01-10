<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Doubles;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;


class FakeServerRequest implements ServerRequestInterface
{
    public string           $method;
    public ?UriInterface    $uri;
    public ?StreamInterface $body;

    public array $attr    = [];
    public array $cookies = [];
    public array $parsed  = [];

    public function __construct(string $method = 'GET', ?UriInterface $uri = null)
    {
        $this->method = $method;
        $this->uri    = $uri;
    }

    public function getMethod(): string
    {
        return $this->method ?: 'GET';
    }

    public function getUri(): UriInterface
    {
        return $this->uri ?: FakeUri::fromString('//example.com');
    }

    public function getRequestTarget(): string
    {
        $query = $this->getUri()->getquery();
        $path  = $this->getUri()->getPath();

        return $query ? $path . '?' . $query : $path;
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion($version): self
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->attr;
    }

    public function hasHeader($name): bool
    {
        return false;
    }

    public function getHeader($name): array
    {
        return [];
    }

    public function getHeaderLine($name): string
    {
        return '';
    }

    public function withHeader($name, $value): self
    {
        return $this->withAttribute($name, $value);
    }

    public function withAddedHeader($name, $value): self
    {
        return $this;
    }

    public function withoutHeader($name): self
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        return $this;
    }

    public function withRequestTarget($requestTarget): self
    {
        return $this;
    }

    public function withMethod($method): self
    {
        $clone = clone $this;
        $clone->method = $method;
        return $clone;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $clone = clone $this;
        $clone->uri = $uri;
        return $clone;
    }

    public function getServerParams(): array
    {
        return [];
    }

    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    public function withCookieParams(array $cookies): self
    {
        return $this;
    }

    public function getQueryParams(): array
    {
        return [];
    }

    public function withQueryParams(array $query): self
    {
        return $this;
    }

    public function getUploadedFiles(): array
    {
        return [];
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        return $this;
    }

    public function getParsedBody()
    {
        return $this->parsed;
    }

    public function withParsedBody($data): self
    {
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attr;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attr[$name] ?? $default;
    }

    public function withAttribute($name, $value): self
    {
        $clone = clone $this;
        $clone->attr[$name] = $value;
        return $clone;
    }

    public function withoutAttribute($name): self
    {
        $clone = clone $this;
        unset($clone->attr[$name]);
        return $clone;
    }
}
