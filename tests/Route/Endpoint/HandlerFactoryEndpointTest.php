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
use Polymorphine\Routing\Route\Endpoint\HandlerFactoryEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeContainer;
use Polymorphine\Routing\Tests\Doubles\FakeHandlerFactory;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;


class HandlerFactoryEndpointTest extends TestCase
{
    protected $container;

    public function setUp()
    {
        $this->container = new FakeContainer();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->endpoint('SomeClass'));
    }

    public function testHandlerFromFactoryIsCalled()
    {
        $prototype = new FakeResponse();
        $endpoint  = $this->endpoint(FakeHandlerFactory::class);
        $this->assertNotSame($prototype, $endpoint->forward(new FakeServerRequest(), $prototype));
        $this->assertSame('handler response', (string) $endpoint->forward(new FakeServerRequest(), $prototype)->getBody());
    }

    private function endpoint(string $className)
    {
        $callback = function () use ($className) { return new $className; };
        return new HandlerFactoryEndpoint($callback, $this->container);
    }
}
