<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Pattern\UriSegment;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route\Gate\Pattern;
use Polymorphine\Routing\Route\Gate\Pattern\UriSegment\HostSubdomain;
use Polymorphine\Routing\Exception\InvalidUriParamsException;
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class HostSubdomainTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Pattern::class, $this->subdomain('id', ['api', 'www']));
    }

    public function testMatchedSubdomainIsPassedAsAttribute()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $request   = new FakeServerRequest('GET', FakeUri::fromString('http://en.example.com/foo/bar'));
        $this->assertSame('en', $subdomain->matchedRequest($request)->getAttribute('lang'));

        $request = new FakeServerRequest('GET', FakeUri::fromString('http://pl.example.com/foo/bar'));
        $this->assertSame('pl', $subdomain->matchedRequest($request)->getAttribute('lang'));
    }

    public function testNotMatchedSubdomain_ReturnsNull()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $request   = new FakeServerRequest('GET', FakeUri::fromString('http://example.com/foo/bar'));
        $this->assertNull($subdomain->matchedRequest($request));

        $request = new FakeServerRequest('GET', FakeUri::fromString('http://www.example.com/foo/bar'));
        $this->assertNull($subdomain->matchedRequest($request));
    }

    public function testUriMethodAppendsSubdomainParameter()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $prototype = FakeUri::fromString('//example.com/foo/bar?fizz=buzz');
        $this->assertSame('//pl.example.com/foo/bar?fizz=buzz', (string) $subdomain->uri($prototype, ['lang' => 'pl']));
    }

    public function testMissingUriParam_ThrowsException()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $this->expectException(InvalidUriParamsException::class);
        $subdomain->uri(FakeUri::fromString('http://example.com/foo/bar'), ['language' => 'en']);
    }

    public function testMissingPrototypeHost_ThrowsException()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $this->expectException(UnreachableEndpointException::class);
        $subdomain->uri(FakeUri::fromString('http:/foo/bar'), ['lang' => 'en']);
    }

    public function testUndefinedParamValue_ThrowsException()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $this->expectException(UnreachableEndpointException::class);
        $subdomain->uri(FakeUri::fromString('http://example.com/foo/bar'), ['lang' => 'fr']);
    }

    private function subdomain(string $id, array $values)
    {
        return new HostSubdomain($id, $values);
    }
}
