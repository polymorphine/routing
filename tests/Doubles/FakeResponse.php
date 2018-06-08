<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Doubles;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;


class FakeResponse implements ResponseInterface
{
    public $body;
    public $headers  = [];
    public $protocol = '1.1';
    public $status   = 200;
    public $reason   = 'OK';

    /**
     * @var ServerRequestInterface
     */
    public $fromRequest;

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
    }

    public function getHeaderLine($name)
    {
    }

    public function withHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withAddedHeader($name, $value)
    {
        $this->headers[$name][] = $value;
        return $this;
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
        $this->body = $body;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->status;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
    }

    public function getReasonPhrase()
    {
        return $this->reason;
    }
}
