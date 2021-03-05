<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Endpoint;

use Polymorphine\Routing\Route\Endpoint;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * Endpoint passing request to given callback.
 */
class CallbackEndpoint extends Endpoint
{
    private $callback;

    /**
     * @param callable $callback fn(ServerRequestInterface) => ResponseInterface
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    protected function execute(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface
    {
        return ($this->callback)($request);
    }
}
