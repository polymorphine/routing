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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;


class FakeResponse implements ResponseInterface
{
    public string           $body;
    public array            $headers = [];
    public string           $protocol = '1.1';
    public int              $status   = 200;
    public string           $reason   = 'OK';
    public RequestInterface $fromRequest;

    public function __construct($body = '')
    {
        $this->body = $body;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version): self
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return array_key_exists($name, $this->headers);
    }

    public function getHeader($name): array
    {
        return $this->headers[$name];
    }

    public function getHeaderLine($name): string
    {
        return $this->headers[$name][0] ?? '';
    }

    public function withHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = [$value];
        return $clone;
    }

    public function withAddedHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers[$name][] = $value;
        return $clone;
    }

    public function withoutHeader($name): self
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return is_string($this->body) ? new FakeStream($this->body) : $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $clone = clone $this;
        $clone->body = (string) $body;
        return $clone;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        $clone = clone $this;
        $clone->status = $code;
        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reason;
    }
}
