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
            'home'                     => '/',
            'admin.GET'                => '/admin',
            'admin.POST'               => '/admin',
            'login'                    => '/login',
            'logout'                   => '/logout',
            'articles.POST'            => '/articles',
            'articles.PATCH'           => '/articles/((#id))',
            'articles.DELETE'          => '/articles/((#id))',
            'articles.NEW'             => '/articles/new/form',
            'articles.EDIT'            => '/articles/((#id))/form',
            'articles.GET.form.edit'   => '/articles/((#id))/form',
            'articles.GET.form.new'    => '/articles/new/form',
            'articles.GET.item'        => '/articles/((#id))',
            'articles.GET.index'       => '/articles',
            '0.GET'                    => '/login',
            '0.POST'                   => '/login',
            '1'                        => '/logout',
            '2'                        => '/admin',
            '3.home'                   => '/',
            '3.admin.GET'              => '/admin',
            '3.admin.POST'             => '/admin',
            '3.login'                  => '/login',
            '3.logout'                 => '/logout',
            '3.articles.POST'          => '/articles',
            '3.articles.PATCH'         => '/articles/((#id))',
            '3.articles.DELETE'        => '/articles/((#id))',
            '3.articles.NEW'           => '/articles/new/form',
            '3.articles.EDIT'          => '/articles/((#id))/form',
            '3.articles.GET.form.edit' => '/articles/((#id))/form',
            '3.articles.GET.form.new'  => '/articles/new/form',
            '3.articles.GET.item'      => '/articles/((#id))',
            '3.articles.GET.index'     => '/articles',
            '4'                        => '/'
        ];

        $this->assertSame($expected, $routes);
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
