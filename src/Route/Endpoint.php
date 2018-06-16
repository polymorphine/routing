<?php
/**
 * Created by PhpStorm.
 * User: MQs
 * Date: 09-06-2018
 * Time: 16:47
 */

namespace Polymorphine\Routing\Route;


use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception\SwitchCallException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


abstract class Endpoint implements Route
{
    abstract public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface;

    public function select(string $path): Route
    {
        throw new SwitchCallException(sprintf('Gateway not found for path `%s`', $path));
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $prototype;
    }
}
