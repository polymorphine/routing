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


class FakeResponse implements ResponseInterface
{
    public string $body;
    public array  $headers = [];
    public string $protocol = '1.1';
    public int    $status   = 200;
    public string $reason   = 'OK';

    public function __construct($body = '')
    {
        $this->body = $body;
    }

    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version)
    {
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
    }

    public function getHeader($name)
    {
        return $this->headers[$name];
    }

    public function getHeaderLine($name)
    {
    }

    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers[$name] = [$value];
        return $clone;
    }

    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers[$name][] = $value;
        return $clone;
    }

    public function withoutHeader($name)
    {
    }

    public function getBody()
    {
        return is_string($this->body) ? new FakeStream($this->body) : $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;
        $clone->body = (string) $body;
        return $clone;
    }

    public function getStatusCode()
    {
        return $this->status;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        $clone = clone $this;
        $clone->status = $code;
        return $clone;
    }

    public function getReasonPhrase()
    {
        return $this->reason;
    }
}
