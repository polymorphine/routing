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
    public $uriPrototype;
    public $uriParams;
    public $uriResult;

    public $passedRequest;
    public $matchedRequest;

    private $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $this->passedRequest = $request;
        return $this->path ? $this->matchedRequest = $request->withAttribute('pattern', 'passed') : null;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $this->uriPrototype = $prototype;
        $this->uriParams    = $params;
        return $this->uriResult = $prototype->withPath($this->path ?: '/');
    }

    public function templateUri(UriInterface $uri): UriInterface
    {
        return $this->uri($uri, []);
    }
}
