<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests;

use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\MockedRoute;
use Psr\Http\Message\ServerRequestInterface;


trait EndpointTestMethods
{
    private static $prototype;

    public static function setUpBeforeClass()
    {
        self::$prototype = new FakeResponse();
    }

    private function callbackResponse(&$response, string $body = '')
    {
        $response = new FakeResponse($body);
        return function (ServerRequestInterface $request) use (&$response) {
            $response->fromRequest = $request;
            return $response;
        };
    }

    private function responseRoute(&$response, string $body = '')
    {
        $response = new FakeResponse($body);
        return new MockedRoute($response);
    }
}
