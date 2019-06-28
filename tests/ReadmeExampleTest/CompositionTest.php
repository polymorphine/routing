<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\ReadmeExampleTest;

use Polymorphine\Routing\Tests\ReadmeExampleTest;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Route\Endpoint\RedirectEndpoint;
use Polymorphine\Routing\Route\Gate\CallbackGateway;
use Polymorphine\Routing\Route\Gate\MiddlewareGateway;
use Polymorphine\Routing\Route\Gate\MethodGate;
use Polymorphine\Routing\Route\Gate\PathSegmentGate;
use Polymorphine\Routing\Route\Gate\UriAttributeSelect;
use Polymorphine\Routing\Route\Gate\PatternGate;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\Path;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Route\Splitter\PathSwitch;
use Polymorphine\Routing\Route\Splitter\ScanSwitch;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class CompositionTest extends ReadmeExampleTest
{
    protected function router(): Router
    {
        if ($this->router) { return $this->router; }

        $mainRoute = new PathSwitch([
            'admin' => new MethodSwitch([
                'GET'  => $this->callbackEndpoint('AdminPanel'),
                'POST' => $this->callbackEndpoint('ApplySettings')
            ]),
            'login' => new RedirectEndpoint(function () {
                return $this->router->uri('home');
            }),
            'logout' => new MethodGate('POST', $this->callbackEndpoint('Logout')),
            'articles' => new UriAttributeSelect(new MethodSwitch([
                'GET' => new ScanSwitch([
                    'form' => new UriAttributeSelect(new ScanSwitch([
                        'edit' => new PatternGate(
                            new PathSegment('id'),
                            new PatternGate(new Path('form'), $this->callbackEndpoint('EditArticleForm'))
                        ),
                        'new' => new PatternGate(new Path('new/form'), $this->callbackEndpoint('AddArticleForm'))
                    ]), 'id', 'edit', 'new'),
                    'item'  => new PatternGate(new PathSegment('id'), $this->callbackEndpoint('ShowArticle')),
                    'index' => new PatternGate(new Path(''), $this->callbackEndpoint('ShowArticles'))
                ]),
                'POST'   => new PatternGate(new Path(''), $this->callbackEndpoint('AddArticle')),
                'DELETE' => new PatternGate(new PathSegment('id'), $this->callbackEndpoint('DeleteArticle')),
                'PATCH'  => new PatternGate(new PathSegment('id'), $this->callbackEndpoint('UpdateArticle'))
            ]), 'id', 'item', 'index')
        ], $this->callbackEndpoint('HomePage'), 'home');

        $route = new MiddlewareGateway(
            $this->csrfMiddleware(),
            new MiddlewareGateway(
                $this->authMiddleware(),
                new ScanSwitch([
                    new PathSegmentGate('login', new MethodSwitch([
                        'GET'  => $this->callbackEndpoint('LoginPage'),
                        'POST' => $this->callbackEndpoint('Login')
                    ])),
                    new PathSegmentGate('logout', new RedirectEndpoint(function () {
                        return $this->router->uri('home');
                    })),
                    new PathSegmentGate('admin', new RedirectEndpoint(function () {
                        return $this->router->uri('login');
                    })),
                    new MethodGate('GET', $mainRoute),
                    new CallbackEndpoint($this->notFound())
                ], new CallbackGateway($this->adminGate(), $mainRoute))
            )
        );

        return $this->router = new Router($route, new FakeUri(), new FakeResponse());
    }

    private function callbackEndpoint(string $id): CallbackEndpoint
    {
        return new CallbackEndpoint($this->endpoint($id));
    }
}
