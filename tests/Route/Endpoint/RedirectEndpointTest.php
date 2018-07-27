<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Endpoint;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Endpoint\RedirectEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class RedirectEndpointTest extends TestCase
{
    private function redirect(string $uri)
    {
        $uriCallback = function () use ($uri) {
            return (string) FakeUri::fromString($uri);
        };

        return new RedirectEndpoint($uriCallback);
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->redirect('/foo/bar'));
    }

    public function testRequest_ReturnsRedirectResponse()
    {
        $response = $this->redirect('/foo/bar')->forward(new FakeServerRequest(), new FakeResponse());
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/foo/bar', $response->headers['Location']);
    }
}