<?php declare(strict_types=1);

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
    public function test_Instantiation()
    {
        $this->assertInstanceOf(Pattern::class, $this->domain('example.com'));
    }

    public function test_MatchedRequest_ReturnsRequest()
    {
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('//test.example.com/foo/bar'));
        $this->assertSame($request, $this->domain('example.com')->matchedRequest($request));
    }

    public function test_NotMatchedRequest_ReturnsNull()
    {
        $request = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('//test.example.com/foo/bar'));
        $this->assertNull($this->domain('example.pl')->matchedRequest($request));
    }

    public function test_Uri_ReturnsPrototypeWithHost()
    {
        $prototype = Doubles\FakeUri::fromString('https:/foo/bar');
        $domain    = $this->domain('example.com');
        $this->assertSame('https://example.com/foo/bar', (string) $domain->uri($prototype, []));
    }

    public function test_Uri_ForPrototypeWithDifferentHost_ThrowsException()
    {
        $domain    = $this->domain('example.com');
        $prototype = Doubles\FakeUri::fromString('https://example.pl/foo/bar');
        $this->expectException(Exception\InvalidUriPrototypeException::class);
        $domain->uri($prototype, []);
    }

    public function test_TemplateUri_ReturnsUriWithHostDomain()
    {
        $uri    = Doubles\FakeUri::fromString('https:/foo/bar');
        $domain = $this->domain('example.com');
        $this->assertSame('https://example.com/foo/bar', (string) $domain->templateUri($uri));
    }

    public function test_TemplateUri_ForPrototypeWithDifferentHost_ThrowsException()
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
