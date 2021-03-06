<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing;

use Polymorphine\Routing\Map\Trace;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


interface Route
{
    public const PATH_SEPARATOR     = '.';
    public const PATH_ATTRIBUTE     = 'route.path';
    public const WILDCARD_ATTRIBUTE = 'route.path.wildcard';
    public const METHODS_ATTRIBUTE  = 'route.methods';

    /**
     * Forward $request and handle it from matching endpoint Route(s).
     * Returns the same instance of response prototype if request was not handled.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $prototype
     *
     * @return ResponseInterface
     */
    public function forward(ServerRequestInterface $request, ResponseInterface $prototype): ResponseInterface;

    /**
     * Route located behind last switch defined with provided identifiers path.
     *
     * @param string $path by default dot separated switch identifiers relative
     *                     to current position in routing tree
     *
     * @throws Route\Exception\RouteNotFoundException
     *
     * @return Route
     */
    public function select(string $path): Route;

    /**
     * Get endpoint call Uri.
     *
     * Uri itself used for incoming ServerRequest does not guarantee
     * reaching current endpoint as other conditions might reject it
     * (http method, authorization... etc.), but returned Uri parts
     * are required by this endpoint to pass.
     *
     * Returned Uri segments MUST match those compared in forward() method.
     * Other segments SHOULD NOT be added, and $prototype MUST NOT define
     * different segments than returned from current route instance.
     * If any Uri part defined in $prototype is overwritten with different
     * value InvalidUriPrototypeException SHOULD be thrown.
     *
     * If Route is not an endpoint for any ServerRequestInterface or cannot be
     * resolved into endpoint's Uri UndefinedUriException MUST be thrown
     *
     * Redundant $params SHOULD be ignored, but if Uri cannot be built with
     * given $params method MUST throw InvalidUriParamException
     *
     * @param array        $params
     * @param UriInterface $prototype
     *
     * @throws Route\Exception\UriBuildException
     *
     * @return UriInterface
     */
    public function uri(UriInterface $prototype, array $params): UriInterface;

    /**
     * Gathers and stores inside Map object all routing paths associated with
     * information about request methods and URI templates.
     *
     * @param Trace $trace
     *
     * @throws Map\Exception\UnreachableEndpointException
     */
    public function routes(Trace $trace): void;
}
