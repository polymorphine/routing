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
use Polymorphine\Routing\Map\Path;
use Polymorphine\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;


abstract class ReadmeExampleTest extends TestCase
{
    /** @var Router */
    protected $router;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Router::class, $this->router());
    }

    /**
     * @dataProvider endpointRequests
     *
     * @param string                 $expectedOutput
     * @param ServerRequestInterface $request
     * @param string                 $routePath
     * @param array                  $uriParams
     */
    public function testRequestCanReachItsEndpoint(
        string $expectedOutput,
        ServerRequestInterface $request,
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
     * @param ServerRequestInterface $request
     * @param string                 $locationRoutePath
     */
    public function testRedirectedRequests(ServerRequestInterface $request, string $locationRoutePath)
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

    public function testRouterCanProduceRoutingMap()
    {
        $routes = $this->router()->routes();
        $expected = [
            new Path('home', '*', '/'),
            new Path('admin', 'GET', '/admin'),
            new Path('admin.GET', 'GET', '/admin'),
            new Path('admin.POST', 'POST', '/admin'),
            new Path('login', '*', '/login'),
            new Path('logout', 'POST', '/logout'),
            new Path('articles.form.edit', 'GET', '/articles/{#id}/form'),
            new Path('articles.form.new', 'GET', '/articles/new/form'),
            new Path('articles.item', 'GET', '/articles/{#id}'),
            new Path('articles.index', 'GET', '/articles'),
            new Path('articles.POST', 'POST', '/articles'),
            new Path('articles.PATCH', 'PATCH', '/articles/{#id}'),
            new Path('articles.DELETE', 'DELETE', '/articles/{#id}'),
            new Path('articles.GET.form.edit', 'GET', '/articles/{#id}/form'),
            new Path('articles.GET.form.new', 'GET', '/articles/new/form'),
            new Path('articles.GET.item', 'GET', '/articles/{#id}'),
            new Path('articles.GET.index', 'GET', '/articles'),
            new Path('0', 'GET', '/login'),
            new Path('0.GET', 'GET', '/login'),
            new Path('0.POST', 'POST', '/login'),
            new Path('1', '*', '/logout'),
            new Path('2', '*', '/admin'),
            new Path('3', 'GET', '/'),
            new Path('3.admin', 'GET', '/admin'),
            new Path('3.admin.GET', 'GET', '/admin'),
            new Path('3.login', 'GET', '/login'),
            new Path('3.articles.form.edit', 'GET', '/articles/{#id}/form'),
            new Path('3.articles.form.new', 'GET', '/articles/new/form'),
            new Path('3.articles.item', 'GET', '/articles/{#id}'),
            new Path('3.articles.index', 'GET', '/articles'),
            new Path('3.articles.GET.form.edit', 'GET', '/articles/{#id}/form'),
            new Path('3.articles.GET.form.new', 'GET', '/articles/new/form'),
            new Path('3.articles.GET.item', 'GET', '/articles/{#id}'),
            new Path('3.articles.GET.index', 'GET', '/articles'),
            new Path('4', '*', '/')
        ];

        $this->assertEquals($expected, $routes);
    }

    abstract protected function router(): Router;

    protected function endpoint(string $id): callable
    {
        return function (ServerRequestInterface $request) use ($id) {
            if ($resourceId = $request->getAttribute('id')) {
                $id .= '(' . $resourceId . ')';
            }
            return new Doubles\FakeResponse($id);
        };
    }

    protected function notFound(): callable
    {
        return function () {
            $response = new Doubles\FakeResponse();
            return $response->withStatus(404, 'Not Found');
        };
    }

    protected function csrfMiddleware(): MiddlewareInterface
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

    protected function authMiddleware(): MiddlewareInterface
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

    protected function adminGate(): callable
    {
        return function (ServerRequestInterface $request): ?ServerRequestInterface {
            return $request->getAttribute('userRole') === 'admin' ? $request : null;
        };
    }

    private function request(string $method, string $uri, array $attributes = []): ServerRequestInterface
    {
        $request = new Doubles\FakeServerRequest($method, Doubles\FakeUri::fromString($uri));
        $request->attr = $attributes;
        return $request;
    }
}
