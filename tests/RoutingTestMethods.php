<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests;

use Psr\Http\Message\RequestInterface;


trait RoutingTestMethods
{
    private static Doubles\FakeResponse $prototype;

    public static function setUpBeforeClass(): void
    {
        self::$prototype = new Doubles\FakeResponse();
    }

    /** @return callable fn(RequestInterface) => Doubles\FakeResponse */
    private function callbackResponse(?Doubles\FakeResponse &$response, string $body = ''): callable
    {
        $response = new Doubles\FakeResponse($body);
        return function (RequestInterface $request) use (&$response) {
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
