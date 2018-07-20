<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Builder\MethodSwitchBuilder;
use Polymorphine\Routing\Builder\RouteBuilder;
use Polymorphine\Routing\Builder\SwitchBuilder;
use Polymorphine\Routing\Builder\ResponseScanSwitchBuilder;
use Polymorphine\Routing\Builder\PathSegmentSwitchBuilder;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Route\Pattern\UriSegment\Path;
use Polymorphine\Routing\Route\Pattern\UriSegment\PathSegment;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;


class RoutingBuilderTest extends TestCase
{
    private $structure;

    public function testInstantiation()
    {
        $this->assertInstanceOf(SwitchBuilder::class, new ResponseScanSwitchBuilder());
        $this->assertInstanceOf(SwitchBuilder::class, new PathSegmentSwitchBuilder());
    }

    public function testBuild_ReturnsRouteSplitter()
    {
        $this->assertInstanceOf(Route\Splitter\ResponseScanSwitch::class, (new ResponseScanSwitchBuilder())->build());
        $this->assertInstanceOf(Route\Splitter\PathSegmentSwitch::class, (new PathSegmentSwitchBuilder())->build());
    }

    public function testBuild_ReturnsRoutingStructure()
    {
        $builder = $this->structureExample();
        $this->assertInstanceOf(Route::class, $builder->build());
    }

    public function testAddingRouteWithAlreadyDefinedName_ThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->structureExample()->route('home')->callback(function () { return new FakeResponse(); });
    }

    public function testAddingMethodSplitterWithUnknownHttpMethod_ThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $builder = new MethodSwitchBuilder();
        $builder->route('INDEX')->callback(function () { return new FakeResponse(); });
    }

    public function testBuildUndefinedRoute_ThrowsException()
    {
        $builder = new RouteBuilder();
        $this->expectException(\Exception::class);
        $builder->build();
    }

    public function testSetRouteWhenAlreadyBuilt_ThrowsException()
    {
        $builder = new RouteBuilder();
        $builder->callback(function () {});
        $this->expectException(\Exception::class);
        $builder->pathSwitch();
    }

    public function testStructureIntegrity()
    {
        $routes    = $this->structureExample()->build();
        $prototype = new FakeResponse('proto');

        $this->assertSame($prototype, $routes->forward(new FakeServerRequest(), $prototype));

        $request = $this->matchingRequest($routes, 'paths.user.index');
        $this->assertSame('users.index', (string) $routes->forward($request, $prototype)->getBody());

        $request = $this->matchingRequest($routes, 'home');
        $this->assertSame('/', (string) $routes->select('home')->uri(new FakeUri(), []));
        $this->assertSame('home', (string) $routes->forward($request, $prototype)->getBody());

        $request = $this->matchingRequest($routes, 'paths.posts', ['id' => 123], 'OPTIONS');
        $this->assertSame('/posts/123', (string) $routes->select('paths.posts')->uri(new FakeUri(), ['id' => 123]));
        $this->assertSame('paths.posts', (string) $routes->forward($request, $prototype)->getBody());
        $this->assertSame('proto', (string) $routes->forward($request->withMethod('POST'), $prototype)->getBody());

        $request = $this->matchingRequest($routes, 'paths.resource.GET.index', [], 'GET');
        $this->assertSame('paths.resource.GET.index', (string) $routes->forward($request, $prototype)->getBody());

        $request = $this->matchingRequest($routes, 'paths.resource.GET.item', ['id' => 714], 'GET');
        $this->assertSame('paths.resource.GET.item.714', (string) $routes->forward($request, $prototype)->getBody());

        $request = $this->matchingRequest($routes, 'paths.resource.DELETE', ['id' => 714], 'DELETE');
        $this->assertSame('paths.resource.DELETE', (string) $routes->forward($request, $prototype)->getBody());
    }

    public function testLinkedRoute()
    {
        $routes = $this->structureExample()->build();
        $proto  = new FakeResponse('proto');
        $paths  = ['paths', 'paths.home', 'index'];
        foreach ($paths as $path) {
            $request = $this->matchingRequest($routes, $path, [], 'GET');
            $this->assertSame('home', (string) $routes->forward($request, $proto)->getBody());
        }
    }

    private function structureExample()
    {
        if ($this->structure) { return $this->structure; }

        $routing = new ResponseScanSwitchBuilder();

        $routing->route('home')->get(new Path('/'))->link($home)->callback(function () {
            return new FakeResponse('home');
        });
        $routing->route('index')->pattern(new Path('/index.php'))->join($home);

        $path = $routing->route('paths')->callbackGate(function (ServerRequestInterface $request) {
            return $request->withAttribute('Type', 'json');
        })->pathSwitch();
        $path->route('home')->join($home);
        $path->root($home);

        $users = $path->route('user')->responseScan();
        $users->route('index')->get()->callback(function () {
            return new FakeResponse('users.index');
        });
        $users->route('profile')->get(PathSegment::slug('id'))->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('user.profile.' . $request->getAttribute('id'));
        });
        $users->route('add')->post()->callback(function () {
            return new FakeResponse('user.add');
        });
        $users->route('delete')->delete(PathSegment::slug('id'))->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('user.delete.' . $request->getAttribute('id'));
        });
        $users->route('update')->put(PathSegment::slug('id'))->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('user.update.' . $request->getAttribute('id'));
        });
        $users->route('newInfo')->patch(PathSegment::slug('id'))->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('user.newInfo.' . $request->getAttribute('id'));
        });

        $path->route('posts')->options(PathSegment::number())->callback(function () { return new FakeResponse('paths.posts'); });
        $path->route('posts.ping')->head(PathSegment::number())->callback(function () { return new FakeResponse('paths.posts.ping'); });

        $res = $path->route('resource')->callbackGate(function (ServerRequestInterface $request) {
            return $request->getAttribute('Type') === 'json' ? $request : null;
        })->methodSwitch();

        $resGET = $res->route('GET')->responseScan();
        $resGET->route('index')->pattern(new Path('/resource'))->callback(function () { return new FakeResponse('paths.resource.GET.index'); });
        $resGET->route('item')->pattern(PathSegment::number())->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('paths.resource.GET.item.' . $request->getAttribute('id'));
        });
        $res->route('POST')->pattern(new Path('/resource'))->callback(function () { return new FakeResponse('paths.resource.POST'); });
        $res->route('DELETE')->pattern(PathSegment::number())->callback(function () { return new FakeResponse('paths.resource.DELETE'); });

        return $routing;
    }

    private function matchingRequest(
        Route $routes,
        string $path,
        array $params = [],
        string $method = 'GET'
    ): ServerRequestInterface {
        $uri = $routes->select($path)->uri(new FakeUri(), $params);
        return new FakeServerRequest($method, $uri);
    }
}
