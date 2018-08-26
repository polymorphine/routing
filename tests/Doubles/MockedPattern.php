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

use Polymorphine\Routing\Route\Gate\Pattern;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class MockedPattern implements Pattern
{
    private $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $uri = (string) $request->getUri();
        return ($uri === $this->path) ? $request->withAttribute('pattern', 'passed') : null;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return FakeUri::fromString($this->path);
    }
}
