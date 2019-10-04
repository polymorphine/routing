<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\Route\Gate\Pattern\UriPart;

use PHPUnit\Framework\TestCase;
use Polymorphine\Routing\Route\Gate\Pattern;
use Polymorphine\Routing\Exception;
use Polymorphine\Routing\Tests\Doubles;


class HostSubdomainTest extends TestCase
{
    use Pattern\UriTemplatePlaceholder;

    public function testInstantiation()
    {
        $this->assertInstanceOf(Pattern::class, $this->subdomain('id', ['api', 'www']));
    }

    public function testMatchedSubdomainIsPassedAsAttribute()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://en.example.com/foo/bar'));
        $this->assertSame('en', $subdomain->matchedRequest($request)->getAttribute('lang'));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://pl.example.com/foo/bar'));
        $this->assertSame('pl', $subdomain->matchedRequest($request)->getAttribute('lang'));
    }

    public function testNotMatchedSubdomain_ReturnsNull()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $request   = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://example.com/foo/bar'));
        $this->assertNull($subdomain->matchedRequest($request));

        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('http://www.example.com/foo/bar'));
        $this->assertNull($subdomain->matchedRequest($request));
    }

    public function testUriMethodAppendsSubdomainParameter()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $prototype = Doubles\FakeUri::fromString('//example.com/foo/bar?fizz=buzz');
        $this->assertSame('//pl.example.com/foo/bar?fizz=buzz', (string) $subdomain->uri($prototype, ['lang' => 'pl']));
    }

    public function testTemplateUri_ReturnsUriWithSubdomain()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $uri       = Doubles\FakeUri::fromString('//example.com/foo/bar?fizz=buzz');
        $expected  = $uri->withHost($this->placeholder('lang:en|pl|de') . '.' . $uri->getHost());
        $this->assertEquals($expected, $subdomain->templateUri($uri));
    }

    public function testMissingUriParam_ThrowsException()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $this->expectException(Exception\InvalidUriParamsException::class);
        $subdomain->uri(Doubles\FakeUri::fromString('http://example.com/foo/bar'), ['language' => 'en']);
    }

    public function testMissingPrototypeHost_ThrowsException()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $this->expectException(Exception\UnreachableEndpointException::class);
        $subdomain->uri(Doubles\FakeUri::fromString('http:/foo/bar'), ['lang' => 'en']);
    }

    public function testUndefinedParamValue_ThrowsException()
    {
        $subdomain = $this->subdomain('lang', ['en', 'pl', 'de']);
        $this->expectException(Exception\InvalidUriParamsException::class);
        $subdomain->uri(Doubles\FakeUri::fromString('http://example.com/foo/bar'), ['lang' => 'fr']);
    }

    private function subdomain(string $id, array $values)
    {
        return new Pattern\UriPart\HostSubdomain($id, $values);
    }
}
