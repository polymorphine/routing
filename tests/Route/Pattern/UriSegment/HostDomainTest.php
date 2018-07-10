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
use Polymorphine\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Routing\Route\Pattern;
use Polymorphine\Routing\Route\Pattern\UriSegment\HostDomain;
use Polymorphine\Routing\Tests\Doubles\FakeServerRequest;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class HostDomainTest extends TestCase
{
    private function domain(string $domain)
    {
        return new HostDomain($domain);
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Pattern::class, $this->domain('example.com'));
    }

    public function testMatchingRequest_ReturnsRequest()
    {
        $request = new FakeServerRequest('GET', FakeUri::fromString('//test.example.com/foo/bar'));
        $this->assertSame($request, $this->domain('example.com')->matchedRequest($request));
    }

    public function testNotMatchingRequest_ReturnsNull()
    {
        $request = new FakeServerRequest('GET', FakeUri::fromString('//test.example.com/foo/bar'));
        $this->assertNull($this->domain('example.pl')->matchedRequest($request));
    }

    public function testUri_ReturnsPrototypeWithHost()
    {
        $prototype = FakeUri::fromString('https:/foo/bar');
        $domain    = $this->domain('example.com');
        $this->assertSame('https://example.com/foo/bar', (string) $domain->uri($prototype, []));
    }

    public function testUriGivenPrototypeWithDifferentHost_ThrowsException()
    {
        $prototype = FakeUri::fromString('https://example.pl/foo/bar');
        $this->expectException(UnreachableEndpointException::class);
        $this->assertSame('https://example.com/foo/bar', $this->domain('example.com')->uri($prototype, []));
    }
}
