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
use Polymorphine\Routing\Route\Exception;
use Polymorphine\Routing\Tests\Doubles;


class HostDomainTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Pattern::class, $this->domain('example.com'));
    }

    public function testMatchingRequest_ReturnsRequest()
    {
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('//test.example.com/foo/bar'));
        $this->assertSame($request, $this->domain('example.com')->matchedRequest($request));
    }

    public function testNotMatchingRequest_ReturnsNull()
    {
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('//test.example.com/foo/bar'));
        $this->assertNull($this->domain('example.pl')->matchedRequest($request));
    }

    public function testUri_ReturnsPrototypeWithHost()
    {
        $prototype = Doubles\FakeUri::fromString('https:/foo/bar');
        $domain    = $this->domain('example.com');
        $this->assertSame('https://example.com/foo/bar', (string) $domain->uri($prototype, []));
    }

    public function testUriGivenPrototypeWithDifferentHost_ThrowsException()
    {
        $domain    = $this->domain('example.com');
        $prototype = Doubles\FakeUri::fromString('https://example.pl/foo/bar');
        $this->expectException(Exception\InvalidUriPrototypeException::class);
        $domain->uri($prototype, []);
    }

    public function testTemplateUri_ReturnsUriWithHostDomain()
    {
        $uri    = Doubles\FakeUri::fromString('https:/foo/bar');
        $domain = $this->domain('example.com');
        $this->assertSame('https://example.com/foo/bar', (string) $domain->templateUri($uri));
    }

    public function testTemplateUriGivenPrototypeWithDifferentHost_ThrowsException()
    {
        $domain    = $this->domain('example.com');
        $prototype = Doubles\FakeUri::fromString('https://example.pl/foo/bar');
        $this->expectException(Exception\InvalidUriPrototypeException::class);
        $domain->templateUri($prototype);
    }

    private function domain(string $domain)
    {
        return new Pattern\UriPart\HostDomain($domain);
    }
}
