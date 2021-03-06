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

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Map\Trace;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Exception;


class MockedRoute implements Route
{
    public ServerRequestInterface $forwardedRequest;
    public ?ResponseInterface     $response;
    public ?UriInterface          $uri;
    public UriInterface           $prototype;
    public array                  $params;
    public ?string                $path  = null;
    public ?Trace                 $trace = null;
    public MockedRoute            $subRoute;
    public ?Exception             $exception = null;

    /** @var callable */
    public $traceCallback;

    public function __construct(?ResponseInterface $response = null, ?UriInterface $uri = null)
    {
        $this->response = $response;
        $this->uri      = $uri;
    }

    public static function response(string $response): self
    {
        return new self(new FakeResponse($response), null);
    }

    public static function withUri(string $uri): self
    {
        return new self(null, FakeUri::fromString($uri));
    }

    public static function withTraceCallback(callable $callback): self
    {
        $route = new self();
        $route->traceCallback = $callback;
        return $route;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        $this->forwardedRequest = $request;
        return $this->response ?? $prototype;
    }

    public function select(string $path): Route
    {
        $this->path     = $path;
        $this->subRoute = clone $this;
        unset($this->subRoute->path);
        return $this->subRoute;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        if ($this->exception) { throw $this->exception; }

        $this->prototype = $prototype;
        $this->params    = $params;
        return $this->uri ?: $prototype;
    }

    public function routes(Trace $trace): void
    {
        if ($this->traceCallback) { ($this->traceCallback)($trace); }
        $this->trace = $trace;
        $trace->endpoint();
    }
}
