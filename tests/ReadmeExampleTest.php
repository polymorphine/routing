<?php declare(strict_types=1);

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
    protected ?Router $router = null;

    public static function endpointRequests(): iterable
    {
        $admin = ['authenticate' => 'admin'];

        return [
            ['LoginPage', ['GET', '/login'], 'login'],
            ['Login', ['POST', '/login'], 'login'],
            ['HomePage', ['GET', '/'], 'home'],
            ['AdminPanel', ['GET', '/admin', $admin], 'admin'],
            ['ApplySettings', ['POST', '/admin', $admin], 'admin'],
            ['Logout', ['POST', '/logout', $admin], 'logout'],
            ['ShowArticles', ['GET', '/articles'], 'articles'],
            ['ShowArticle(123)', ['GET', '/articles/123'], 'articles', ['id' => 123]],
            ['AddArticle', ['POST', '/articles', $admin], 'articles'],
            ['AddArticle', ['POST', '/articles', $admin], 'articles.POST'],
            ['UpdateArticle(234)', ['PATCH', '/articles/234', $admin], 'articles', ['id' => 234]],
            ['DeleteArticle(87)', ['DELETE', '/articles/87', $admin], 'articles', ['id' => 87]],
            ['AddArticleForm', ['GET', '/articles/new/form'], 'articles.form'],
            ['EditArticleForm(22)', ['GET', '/articles/22/form'], 'articles.form', ['id' => 22]]
        ];
    }

    public static function redirectedRequests(): iterable
    {
        return [
            'Logout when not logged in'     => [['POST', '/logout'], 'home'],
            'AdminPanel when not logged in' => [['', '/admin'], 'login'],
            'Login when already logged in'  => [['POST', '/login', ['authenticate' => 'admin']], 'home']
        ];
    }

    public function test_Instantiation()
    {
        $this->assertInstanceOf(Router::class, $this->router());
    }

    /** @dataProvider endpointRequests */
    public function test_Request_CanReachItsEndpoint(
        string $expectedOutput,
        array $requestData,
        string $routePath,
        array $uriParams = []
    ) {
        $router  = $this->router();
        $request = $this->request(...$requestData);

        $responseBody = (string) $router->handle($request)->getBody();
        $this->assertSame($expectedOutput, $responseBody);

        $uriString = (string) $request->getUri();
        $this->assertSame($uriString, (string) $router->uri($routePath, $uriParams));
    }

    /** @dataProvider redirectedRequests */
    public function test_RedirectedRequests(array $requestData, string $locationRoutePath)
    {
        $router   = $this->router();
        $request  = $this->request(...$requestData);
        $response = $router->handle($request);
        $this->assertSame((string) $router->uri($locationRoutePath), (string) $response->getHeader('Location')[0]);
    }

    public function test_Router_CanProduceRoutingMap()
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
        return new class implements MiddlewareInterface {
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
        return new class implements MiddlewareInterface {
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
