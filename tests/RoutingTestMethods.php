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


trait RoutingTestMethods
{
    private static $prototype;

    public static function setUpBeforeClass()
    {
        self::$prototype = new Doubles\FakeResponse();
    }

    private function callbackResponse(&$response, string $body = '')
    {
        $response = new Doubles\FakeResponse($body);
        return function ($request) use (&$response) {
            $response->fromRequest = $request;
            return $response;
        };
    }

    private function responseRoute(&$response, string $body = '')
    {
        $response = new Doubles\FakeResponse($body);
        return new Doubles\MockedRoute($response);
    }
}
