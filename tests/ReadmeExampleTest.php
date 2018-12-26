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

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Builder\RoutingBuilder;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest as Request;
use Polymorphine\Routing\Tests\Doubles\FakeUri as Uri;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;


class ReadmeExampleTest extends TestCase
{
    private $router;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Router::class, $this->router());
    }

    /**
     * @dataProvider endpointRequests
     *
     * @param string  $expectedOutput
     * @param Request $request
     * @param string  $routePath
     * @param array   $uriParams
     */
    public function testRequestReachItsEndpoint(
        string $expectedOutput,
        Request $request,
        string $routePath,
        array $uriParams = []
    ) {
        $router = $this->router();

        $responseBody = (string) $router->handle($request)->getBody();
        $this->assertSame($expectedOutput, $responseBody);

        $uriString = (string) $request->getUri();
        $this->assertSame($uriString, (string) $router->uri($routePath, $uriParams));
    }

    public function endpointRequests()
    {
        $admin = ['authenticate' => 'admin'];

        return [
            ['LoginPage', $this->request('GET', '/login'), 'login'],
            ['Login', $this->request('POST', '/login'), 'login'],
            ['HomePage', $this->request('GET', '/'), 'home'],
            ['AdminPanel', $this->request('GET', '/admin', $admin), 'admin'],
            ['ApplySettings', $this->request('POST', '/admin', $admin), 'admin'],
            ['Logout', $this->request('POST', '/logout', $admin), 'logout'],
            ['ShowArticles', $this->request('GET', '/articles'), 'articles'],
            ['ShowArticle(123)', $this->request('GET', '/articles/123'), 'articles', ['id' => 123]],
            ['AddArticle', $this->request('POST', '/articles', $admin), 'articles'],
            ['AddArticle', $this->request('POST', '/articles', $admin), 'articles.POST'],
            ['UpdateArticle(234)', $this->request('PATCH', '/articles/234', $admin), 'articles', ['id' => 234]],
            ['DeleteArticle(87)', $this->request('DELETE', '/articles/87', $admin), 'articles', ['id' => 87]],
            ['AddArticleForm', $this->request('GET', '/articles/new/form'), 'articles.form'],
            ['EditArticleForm(22)', $this->request('GET', '/articles/22/form'), 'articles.form', ['id' => 22]]
        ];
    }

    /**
     * @dataProvider redirectedRequests
     *
     * @param Request $request
     * @param string  $locationRoutePath
     */
    public function testRedirectedRequests(Request $request, string $locationRoutePath)
    {
        $router   = $this->router();
        $response = $router->handle($request);
        $this->assertSame((string) $router->uri($locationRoutePath), (string) $response->getHeader('Location')[0]);
    }

    public function redirectedRequests()
    {
        return [
            'Logout when not logged in'     => [$this->request('POST', '/logout'), 'home'],
            'AdminPanel when not logged in' => [$this->request('', '/admin'), 'login'],
            'Login when already logged in'  => [$this->request('POST', '/login', ['authenticate' => 'admin']), 'home']
        ];
    }

    private function router(): Router
    {
        if ($this->router) { return $this->router; }

        $baseUri      = new Uri();
        $nullResponse = new FakeResponse();
        $notFound     = $this->notFound();
        $csrf         = $this->csrfMiddleware();
        $auth         = $this->authMiddleware();
        $adminGate    = $this->adminGate();

        $builder = new RoutingBuilder($baseUri, $nullResponse);
        $root    = $builder->rootNode()->middleware($csrf)->middleware($auth)->responseScan();
        $main    = $root->defaultRoute()->callbackGate($adminGate)->link($filteredGuestRoute)->pathSwitch();

        $root->route()->path('/login')->methodSwitch([
            'GET'  => new CallbackEndpoint($this->endpoint('LoginPage')),
            'POST' => new CallbackEndpoint($this->endpoint('Login'))
        ]);
        $root->route()->path('/logout')->redirect('home');
        $root->route()->path('/admin')->redirect('login');
        $root->route()->method('GET')->joinLink($filteredGuestRoute);
        $root->route()->callback($notFound);

        $main->root('home')->callback($this->endpoint('HomePage'));
        $admin = $main->route('admin')->methodSwitch();
        $admin->route('GET')->callback($this->endpoint('AdminPanel'));
        $admin->route('POST')->callback($this->endpoint('ApplySettings'));
        $main->route('login')->redirect('home');
        $main->route('logout')->method('POST')->callback($this->endpoint('Logout'));
        $articles = $main->resource('articles')->id('id');
        $articles->index()->callback($this->endpoint('ShowArticles'));
        $articles->get()->callback($this->endpoint('ShowArticle'));
        $articles->post()->callback($this->endpoint('AddArticle'));
        $articles->patch()->callback($this->endpoint('UpdateArticle'));
        $articles->delete()->callback($this->endpoint('DeleteArticle'));
        $articles->add()->callback($this->endpoint('AddArticleForm'));
        $articles->edit()->callback($this->endpoint('EditArticleForm'));

        return $this->router = $builder->router();
    }

    private function endpoint(string $id): callable
    {
        return function (ServerRequestInterface $request) use ($id) {
            if ($resourceId = $request->getAttribute('id')) {
                $id .= '(' . $resourceId . ')';
            }
            return new FakeResponse($id);
        };
    }

    private function notFound(): callable
    {
        return function () {
            $response = new FakeResponse();
            return $response->withStatus(404, 'Not Found');
        };
    }

    private function csrfMiddleware(): MiddlewareInterface
    {
        return new class() implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                if ($request->getAttribute('csrfTokenError')) {
                    throw new RuntimeException();
                }

                return $handler->handle($request);
            }
        };
    }

    private function authMiddleware(): MiddlewareInterface
    {
        return new class() implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $auth    = $request->getAttribute('authenticate');
                $request = $request->withAttribute('userRole', $auth);
                return $handler->handle($request);
            }
        };
    }

    private function adminGate(): callable
    {
        return function (ServerRequestInterface $request): ?ServerRequestInterface {
            return $request->getAttribute('userRole') === 'admin' ? $request : null;
        };
    }

    private function request(string $method, string $uri, array $attributes = []): Request
    {
        $request = new Request($method, Uri::fromString($uri));
        $request->attr = $attributes;
        return $request;
    }
}
