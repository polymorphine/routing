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

use Psr\Http\Message\StreamInterface;


class FakeStream implements StreamInterface
{
    private string $body;

    public function __construct(string $body = '')
    {
        $this->body = $body;
    }

    public function __toString(): string
    {
        return $this->body;
    }

    public function close(): void
    {
    }

    public function detach()
    {
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return false;
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
    }

    public function rewind(): void
    {
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write($string): int
    {
        return strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        return '';
    }

    public function getContents(): string
    {
        return '';
    }

    public function getMetadata($key = null)
    {
    }
}
