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
use Polymorphine\Routing\Route\Endpoint;
use Polymorphine\Routing\Tests\Doubles;


class RedirectEndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->redirect('/foo/bar'));
    }

    public function testRequest_ReturnsRedirectResponse()
    {
        $response = $this->redirect('/foo/bar')->forward(new Doubles\FakeServerRequest(), new Doubles\FakeResponse());
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(['/foo/bar'], $response->headers['Location']);
    }

    private function redirect(string $uri)
    {
        return new Endpoint\RedirectEndpoint(function () use ($uri) {
            return (string) Doubles\FakeUri::fromString($uri);
        });
    }
}
