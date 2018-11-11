<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UriInterface;


abstract class Endpoint implements Route
{
    public function forward(Request $request, Response $prototype): Response
    {
        return $this->optionsResponse($request, $prototype) ?: $this->execute($request, $prototype);
    }

    public function select(string $path): Route
    {
        throw new Exception\SwitchCallException(sprintf('Gateway not found for path `%s`', $path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $prototype;
    }

    abstract protected function execute(Request $request, Response $prototype): Response;

    private function optionsResponse(Request $request, Response $prototype): ?Response
    {
        if ($request->getMethod() !== 'OPTIONS') { return null; }

        $methods = $request->getAttribute(self::METHODS_ATTRIBUTE);
        return $methods ? $prototype->withHeader('Allow', implode(', ', $methods)) : null;
    }
}
