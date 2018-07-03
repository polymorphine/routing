<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Route\Pattern;

use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Pattern;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;


class StaticUriMask implements Pattern
{
    use PathContextMethods;

    private $pattern;
    private $uri = [];

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
        $this->uri     = $this->groupUriSegments($pattern);
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $uri   = $request->getUri();
        $match = $this->matchScheme($uri) && $this->matchAuthority($uri);
        if (!$match) { return null; }

        $matchedRequest = $this->matchPath($request);
        if (!$matchedRequest) { return null; }

        return $this->matchQuery($matchedRequest);
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        $prototype = $this->setScheme($prototype);
        $prototype = $this->setUserInfo($prototype);
        $prototype = $this->setHost($prototype);
        $prototype = $this->setPort($prototype);
        $prototype = $this->setPath($prototype);

        return $this->setQuery($params, $prototype);
    }

    protected function queryPattern(string $queryString): Pattern
    {
        return new StaticQueryPattern($queryString);
    }

    private function matchScheme(UriInterface $uri): bool
    {
        return !$this->uri['scheme'] || $this->uri['scheme'] === $uri->getScheme();
    }

    private function matchAuthority(UriInterface $uri): bool
    {
        return !$this->uri['authority'] || $this->uri['authority'] === $uri->getAuthority();
    }

    private function matchPath(ServerRequestInterface $request): ?ServerRequestInterface
    {
        if (!$routePath = $this->uri['path']) { return $this->matchEmptyPath($request); }

        $requestPath = ($routePath[0] === '/') ? $request->getUri()->getPath() : $this->relativePath($request);
        if (substr($routePath, -1) !== '*') {
            if ($routePath !== $requestPath) { return null; }
            return $request->withAttribute(Route::PATH_ATTRIBUTE, '');
        }

        $routePath = rtrim($routePath, '*');
        if (strpos($requestPath, $routePath) !== 0) { return null; }
        return $request->withAttribute(Route::PATH_ATTRIBUTE, $this->newPathContext($requestPath, $routePath));
    }

    private function matchEmptyPath(ServerRequestInterface $request)
    {
        if ($this->uri['path'] !== '') { return $request; }
        return ($this->relativePath($request) === '') ? $request : null;
    }

    private function matchQuery(ServerRequestInterface $request)
    {
        $query = $this->uri['query'];
        return ($query) ? $this->queryPattern($query)->matchedRequest($request) : $request;
    }

    private function setScheme(UriInterface $prototype)
    {
        if (!$scheme = $this->uri['scheme']) { return $prototype; }
        $this->checkConflict($scheme, $prototype->getScheme());

        return $prototype->withScheme($scheme);
    }

    private function setUserInfo(UriInterface $prototype)
    {
        if (!$userInfo = $this->uri['userInfo']) { return $prototype; }
        $this->checkConflict($userInfo, $prototype->getUserInfo());

        return $prototype->withUserInfo($this->uri['user'], $this->uri['pass']);
    }

    private function setHost(UriInterface $prototype)
    {
        if (!$host = $this->uri['host']) { return $prototype; }
        $this->checkConflict($host, $prototype->getHost());

        return $prototype->withHost($host);
    }

    private function setPort(UriInterface $prototype)
    {
        if (!$port = $this->uri['port']) { return $prototype; }
        $this->checkConflict($port, $prototype->getPort() ?: '');

        return $prototype->withPort($port);
    }

    private function setPath(UriInterface $prototype)
    {
        if (!$path = rtrim($this->uri['path'], '*')) { return $prototype; }

        $prototypePath = $prototype->getPath();
        if ($path[0] === '/') {
            $this->checkConflict(substr($path, 0, strlen($prototypePath)), $prototypePath);

            return $prototype->withPath($path);
        }

        return $prototype->withPath($prototypePath . '/' . $path);
    }

    private function setQuery(array $params, UriInterface $prototype)
    {
        if (!$query = $this->uri['query']) { return $prototype; }

        return $this->queryPattern($query)->uri($prototype, $params);
    }

    private function checkConflict(string $routeSegment, string $prototypeSegment)
    {
        if ($prototypeSegment && $routeSegment !== $prototypeSegment) {
            $message = 'Uri conflict in `%s` prototype segment for `%s` uri';
            throw new UnreachableEndpointException(sprintf($message, $prototypeSegment, $this->pattern));
        }
    }

    private function groupUriSegments(string $uri): array
    {
        $segments = parse_url($uri);
        if ($segments === false) {
            throw new InvalidArgumentException("Malformed URI string: '${uri}'");
        }

        return [
            'scheme'    => $segments['scheme'] ?? '',
            'user'      => $user = $segments['user'] ?? '',
            'pass'      => $password = $segments['pass'] ?? '',
            'host'      => $host = $segments['host'] ?? '',
            'port'      => $port = (int) ($segments['port'] ?? 0),
            'path'      => $segments['path'] ?? null,
            'query'     => $segments['query'] ?? null,
            'userInfo'  => $userInfo = $password ? $user . ':' . $password : $user,
            'authority' => $this->joinAuthoritySegments($host, $port, $userInfo)
        ];
    }

    private function joinAuthoritySegments($host, $port, $user): string
    {
        if (!$host) { return ''; }
        $user and $host = $user . '@' . $host;
        return $port ? $host . ':' . $port : $host;
    }
}
