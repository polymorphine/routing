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
use Polymorphine\Routing\Route\Gate\UriAttributeSelect;
use Polymorphine\Routing\Route\Gate\PatternGate;
use Polymorphine\Routing\Route\Gate\Pattern\CompositePattern;
use Polymorphine\Routing\Route\Gate\Pattern\UriPart\PathSegment;
use Polymorphine\Routing\Route\Gate\Pattern\UriPart\PathRegexpSegment;
use Polymorphine\Routing\Route\Splitter\MethodSwitch;
use Polymorphine\Routing\Route\Splitter\PathSwitch;
use Polymorphine\Routing\Route\Splitter\ScanSwitch;
use Polymorphine\Routing\Tests\Doubles;


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
                'POST'   => $this->callbackEndpoint('AddArticle'),
                'PATCH'  => new PatternGate(new PathRegexpSegment('id'), $this->callbackEndpoint('UpdateArticle')),
                'DELETE' => new PatternGate(new PathRegexpSegment('id'), $this->callbackEndpoint('DeleteArticle')),
                'GET' => new ScanSwitch([
                    'form' => new UriAttributeSelect(new ScanSwitch([
                        'edit' => new PatternGate(
                            new PathRegexpSegment('id'),
                            new PatternGate(new PathSegment('form'), $this->callbackEndpoint('EditArticleForm'))
                        ),
                        'new' => new PatternGate(
                            new CompositePattern([new PathSegment('new'), new PathSegment('form')]),
                            $this->callbackEndpoint('AddArticleForm')
                        )
                    ]), 'id', 'edit', 'new'),
                    'item'  => new PatternGate(new PathRegexpSegment('id'), $this->callbackEndpoint('ShowArticle')),
                    'index' => $this->callbackEndpoint('ShowArticles')
                ])
            ]), 'id', 'item', 'index')
        ], $this->callbackEndpoint('HomePage'));

        $route = new MiddlewareGateway(
            $this->csrfMiddleware(),
            new MiddlewareGateway(
                $this->authMiddleware(),
                new ScanSwitch([
                    new PatternGate(new PathSegment('login'), new MethodSwitch([
                        'GET'  => $this->callbackEndpoint('LoginPage'),
                        'POST' => $this->callbackEndpoint('Login')
                    ])),
                    new PatternGate(new PathSegment('logout'), new RedirectEndpoint(function () {
                        return $this->router->uri('home');
                    })),
                    new PatternGate(new PathSegment('admin'), new RedirectEndpoint(function () {
                        return $this->router->uri('login');
                    })),
                    new MethodGate('GET', $mainRoute),
                    new CallbackEndpoint($this->notFound())
                ], new CallbackGateway($this->adminGate(), $mainRoute))
            )
        );

        return $this->router = new Router($route, new Doubles\FakeUri(), new Doubles\FakeResponse(), 'home');
    }

    private function callbackEndpoint(string $id): CallbackEndpoint
    {
        return new CallbackEndpoint($this->endpoint($id));
    }
}
