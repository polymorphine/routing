<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Gate\Pattern\UriPart;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


/**
 * Static subdomain pattern that matches one of allowed values
 * and assigning it to request attribute and appending it to
 * prototype URI's domain.
 */
class HostSubdomain implements Route\Gate\Pattern
{
    use Route\Gate\Pattern\UriTemplatePlaceholder;

    private $id;
    private $values = [];

    /**
     * Building URI with subdomain requires prototype with existing
     * domain. If domain is set by pattern gate it needs to be processed
     * before this pattern.
     *
     * @param string   $id     identifier name of assigned attribute and URI build param
     * @param string[] $values allowed subdomain values
     */
    public function __construct(string $id, array $values)
    {
        $this->id     = $id;
        $this->values = $values;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $host = $request->getUri()->getHost();
        [$subdomain, ] = explode('.', $host, 2) + [null, null];

        if (!$subdomain || !in_array($subdomain, $this->values, true)) {
            return null;
        }
        return $request->withAttribute($this->id, $subdomain);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $subdomain = $this->subdomainParameter($params);

        if (!$host = $prototype->getHost()) {
            $message = 'Cannot attach `%s` subdomain to prototype without host';
            throw new Exception\UnreachableEndpointException(sprintf($message, $params[$this->id]));
        }

        return $prototype->withHost($subdomain . '.' . $host);
    }

    public function templateUri(UriInterface $uri): UriInterface
    {
        $definition = $this->id . ':' . implode('|', $this->values);
        return $uri->withHost($this->placeholder($definition) . '.' . $uri->getHost());
    }

    private function subdomainParameter(array $params): string
    {
        if (!isset($params[$this->id])) {
            $message = 'Missing subdomain `%s` parameter';
            throw new Exception\InvalidUriParamsException(sprintf($message, $this->id));
        }

        if (!in_array($params[$this->id], $this->values, true)) {
            $message = 'Invalid parameter value for `%s` subdomain (expected: `%s`)';
            $values  = implode(', ', $this->values);
            throw new Exception\UnreachableEndpointException(sprintf($message, $this->id, $values));
        }

        return $params[$this->id];
    }
}
