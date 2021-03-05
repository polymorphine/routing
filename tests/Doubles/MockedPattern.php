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

use Polymorphine\Routing\Route\Gate\Pattern;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class MockedPattern implements Pattern
{
    public UriInterface $uriPrototype;
    public array        $uriParams;
    public UriInterface $uriResult;

    public ServerRequestInterface $passedRequest;
    public ServerRequestInterface $matchedRequest;

    public ?string $exception = null;

    private ?string $path;

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
        if ($this->exception) {
            $exception = $this->exception;
            throw new $exception();
        }

        $this->uriPrototype = $prototype;
        $this->uriParams    = $params;
        return $this->uriResult = $prototype->withPath($this->path ?: '/');
    }

    public function templateUri(UriInterface $uri): UriInterface
    {
        return $this->uri($uri, []);
    }
}
