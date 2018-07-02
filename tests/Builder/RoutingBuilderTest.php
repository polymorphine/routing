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
use Polymorphine\Routing\Builder\RoutingBuilder;
use Polymorphine\Routing\Builder\ResponseScanSwitchBuilder;
use Polymorphine\Routing\Builder\PathSegmentSwitchBuilder;
use Polymorphine\Routing\Route;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;


class RoutingBuilderTest extends TestCase
{
    private $structure;

    public function testInstantiation()
    {
        $this->assertInstanceOf(RoutingBuilder::class, new ResponseScanSwitchBuilder());
        $this->assertInstanceOf(RoutingBuilder::class, new PathSegmentSwitchBuilder());
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
        $this->structureExample()->endpoint('home')->callback(function () { return new FakeResponse(); });
    }

    public function testAddingMethodSplitterWithUnknownHttpMethod_ThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $builder = new MethodSwitchBuilder();
        $builder->endpoint('INDEX')->callback(function () { return new FakeResponse(); });
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

        $request = $this->matchingRequest($routes, 'paths.posts', [123], 'OPTIONS');
        $this->assertSame('/posts/123', (string) $routes->select('paths.posts')->uri(new FakeUri(), [123]));
        $this->assertSame('paths.posts', (string) $routes->forward($request, $prototype)->getBody());
        $this->assertSame('proto', (string) $routes->forward($request->withMethod('POST'), $prototype)->getBody());

        $request = $this->matchingRequest($routes, 'paths.resource.GET.index', [], 'GET');
        $this->assertSame('paths.resource.GET.index', (string) $routes->forward($request, $prototype)->getBody());

        $request = $this->matchingRequest($routes, 'paths.resource.GET.item', [714], 'GET');
        $this->assertSame('paths.resource.GET.item.714', (string) $routes->forward($request, $prototype)->getBody());

        $request = $this->matchingRequest($routes, 'paths.resource.DELETE', [714], 'DELETE');
        $this->assertSame('paths.resource.DELETE', (string) $routes->forward($request, $prototype)->getBody());
    }

    private function structureExample()
    {
        if ($this->structure) { return $this->structure; }

        $routing = new ResponseScanSwitchBuilder();

        $routing->endpoint('home')->get('/')->callback(function () {
            return new FakeResponse('home');
        });

        $path  = $routing->pathSwitch('paths');
        $users = $path->responseScan('user');
        $users->endpoint('index')->get()->callback(function () {
            return new FakeResponse('users.index');
        });
        $users->endpoint('profile')->get('{$id}')->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('user.profile.' . $request->getAttribute('id'));
        });
        $users->endpoint('add')->post()->callback(function () {
            return new FakeResponse('user.add');
        });
        $users->endpoint('delete')->delete('{$id}')->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('user.delete.' . $request->getAttribute('id'));
        });
        $users->endpoint('update')->put('{$id}')->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('user.update.' . $request->getAttribute('id'));
        });
        $users->endpoint('newInfo')->patch('{$id}')->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('user.newInfo.' . $request->getAttribute('id'));
        });

        $path->endpoint('posts')->options('{#id}')->callback(function () { return new FakeResponse('paths.posts'); });
        $path->endpoint('posts.ping')->head('{#id}')->callback(function () { return new FakeResponse('paths.posts.ping'); });

        $res    = $path->methodSwitch('resource');
        $resGET = $res->responseScan('GET');
        $resGET->endpoint('index')->pattern('/resource')->callback(function () { return new FakeResponse('paths.resource.GET.index'); });
        $resGET->endpoint('item')->pattern('{#id}')->callback(function (ServerRequestInterface $request) {
            return new FakeResponse('paths.resource.GET.item.' . $request->getAttribute('id'));
        });
        $res->endpoint('POST')->pattern('/resource')->callback(function () { return new FakeResponse('paths.resource.POST'); });
        $res->endpoint('DELETE')->pattern('{#id}')->callback(function () { return new FakeResponse('paths.resource.DELETE'); });

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
