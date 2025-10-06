<?php

namespace Makis83\Helpers\Tests;

use Exception;
use Makis83\Helpers\Text;
use Makis83\Helpers\Http;
use Pdp\CannotProcessHost;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for Http helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-29
 * Time: 14:05
 */
#[CoversClass(Http::class)]
#[UsesClass(Text::class)]
class HttpTest extends TestCase
{
    /**
     * Data provider for 'testIp' method.
     *
     * @return array<string, array{0: null|array<string, mixed>, 1: null|non-empty-string, 2: false|string}>
     */
    public static function ipDataProvider(): array
    {
        return [
            'default settings' => [null, null, '127.0.0.1'],
            'custom server and sapi' => [['HTTP_X_REAL_IP' => '192.168.0.1'], 'apache', '192.168.0.1'],
            'custom server and default sapi' => [['HTTP_X_FORWARDED_FOR' => '192.168.0.2'], null, '127.0.0.1'],
            'custom sapi' => [null, 'cgi', false],
            'invalid server' => [['HTTP_X_FORWARDED_FOR' => 'invalid'], 'cgi', false],
            'invalid sapi' => [null, 'invalid', false]
        ];
    }


    /**
     * Test 'ip' method.
     *
     * @param null|array<string, mixed> $server Server and execution environment information
     * @param null|non-empty-string $sapi Server API name
     * @param false|string $expected Expected result
     * @return void
     */
    #[DataProvider('ipDataProvider')]
    final public function testIp(?array $server, ?string $sapi, false|string $expected): void
    {
        $this->assertEquals($expected, Http::ip($server, $sapi));
    }


    /**
     * Data provider for 'testGetDomainFromHost' method.
     *
     * @return array<string, array{0: non-empty-string, 1: null|non-empty-string}>
     */
    public static function getDomainFromHostDataProvider(): array
    {
        return [
            'empty string' => ['  ', null],
            'custom local host' => ['mycustomhost', null],
            'host without TLD (top-level domain)' => ['localhost', null],
            'regular host' => ['example.com', 'example.com'],
            'host with special TLD (doubled)' => ['sub.domain.co.uk', 'domain.co.uk'],
            'host with special TLD (doubled) and multiple subdomains' => [
                'alpha.bravo.foo.bar.com.ua',
                'bar.com.ua'
            ],
            'one subdomain' => ['images.x.com', 'x.com'],
            'host in punycode' => ['xn--80aihfjcshcbin9q.xn--90ais', 'xn--80aihfjcshcbin9q.xn--90ais'],
            'unicode host with multiple subdomains' => ['long.sub.domain.какой-то-сайт.уа', 'какой-то-сайт.уа'],
            'host with multiple subdomains in punycode' => [
                'xn--80aihfjcshcbin9q.xn--90ais.xn--90ais.xn--90ais.xn--90ais',
                'xn--90ais.xn--90ais'
            ]
        ];
    }


    /**
     * Test 'getDomainFromHost' method.
     *
     * @param non-empty-string $host Host name
     * @param null|non-empty-string $expected Expected result
     * @return void
     * @throws CannotProcessHost
     */
    #[DataProvider('getDomainFromHostDataProvider')]
    final public function testGetDomainFromHost(string $host, null|string $expected): void
    {
        $this->assertEquals($expected, Http::getDomainFromHost($host));
    }


    /**
     * Test 'getDomainFromHost' method throws exception on invalid host.
     *
     * @return void
     */
    final public function testGetDomainFromHostThrowsExceptionOnInvalidHost(): void
    {
        $this->expectException(CannotProcessHost::class);
        Http::getDomainFromHost('some random string/path?q=string');
    }


    /**
     * Test 'getDomainFromHost' method throws exception on host with IP address.
     *
     * @return void
     */
    final public function testGetDomainFromHostThrowsExceptionOnIpHost(): void
    {
        $this->expectException(CannotProcessHost::class);
        Http::getDomainFromHost('8.8.8.8');
    }


    /**
     * Data provider for 'testIsValidUrl' method.
     *
     * @return array<string, array{0: non-empty-string, 1: bool}>
     */
    public static function isValidUrlDataProvider(): array
    {
        return [
            'empty string' => ['   ', false],
            'invalid URL' => ['invalid', false],
            'invalid URL without domain' => ['http://', false],
            'invalid URL with un-encoded query param' => ['http://foo.bar?q=Spaces should be encoded', false],
            'invalid URL with wrong scheme' => ['ftps://foo.bar/', false],
            'invalid URL with spaces in domain' => ['http:// shouldfail.com', false],
            'invalid URL with IP address' => ['http://123.123.123', false],
            'invalid URL with wrong domain' => ['http://3628126748', false],
            'invalid URL with dots in host' => ['http://.www.foo.bar/', false],
            'valid HTTP URL without slash at end' => ['http://foo.com/blah_blah', true],
            'valid HTTP URL with slash at end' => ['http://foo.com/blah_blah/', true],
            'valid HTTPS URL' => ['https://foo.com/blah_blah', true],
            'valid FTP URL' => ['ftp://foo.com/blah_blah', true],
            'valid URL without scheme' => ['//localhost.com/blah_blah', true],
            'valid URL with username and port' => ['http://userid@example.com:8080', true],
            'valid URL with query string' => ['http://example.com/path/to/file.html?query=string', true],
            'valid URL with encoded query string' => ['http://foo.bar/?q=Test%20URL-encoded%20stuff', true],
            'valid URL with IP address' => ['http://142.42.1.1/', true],
            'valid URL with unicode domain' => ['http://例子.测试', true],
            'valid URL with cyrillic domain' => ['https://законипорядок.бел', true],
            'valid URL with multiple domains' => ['//long.sub.domain.какой-то-сайт.уа/blah_blah', true],
            'valid URL with punycode domain' => [
                'https://one.two.three.xn--80aihfjcshcbin9q.co.uk./?q=Test%20URL-encoded%20stuff#fdfg',
                true
            ],
            'invalid URL with punycode domain' => [
                'https://one.two.three.xn--0aihfjcshcbin9q.co.uk./?q=Test%20URL-encoded%20stuff#fdfg',
                false
            ]
        ];
    }


    /**
     * Test 'isValidUrl' method.
     *
     * @param non-empty-string $url URL to check
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isValidUrlDataProvider')]
    final public function testIsValidUrl(string $url, bool $expected): void
    {
        $this->assertEquals($expected, Http::isValidUrl($url));
    }


    /**
     * Data provider for 'testGetHostFromUrl' method.
     *
     * @return array<string, array{0: non-empty-string, 1: non-empty-string}>
     */
    public static function getHostFromUrlDataProvider(): array
    {
        return [
            'valid HTTP URL without slash at end' => ['http://foo.com/blah_blah', 'foo.com'],
            'valid HTTP URL with slash at end' => ['http://foo.com/blah_blah/', 'foo.com'],
            'valid HTTPS URL' => ['https://foo.com/blah_blah', 'foo.com'],
            'valid FTP URL' => ['ftp://foo.com/blah_blah', 'foo.com'],
            'valid URL without scheme' => ['//localhost.com/blah_blah', 'localhost.com'],
            'valid URL with username and port' => ['http://userid@example.com:8080', 'example.com'],
            'valid URL with query string' => ['http://example.com/path/to/file.html?query=string', 'example.com'],
            'valid URL with encoded query string' => ['http://foo.bar/?q=Test%20URL-encoded%20stuff', 'foo.bar'],
            'valid URL with IP address' => ['http://142.42.1.1/', '142.42.1.1'],
            'valid URL with unicode domain' => ['http://例子.测试', '例子.测试'],
            'valid URL with multiple domains' => [
                '//long.sub.domain.какой-то-сайт.уа/blah_blah',
                'long.sub.domain.какой-то-сайт.уа'
            ],
            'valid URL with punycode domain' => [
                'https://one.two.three.xn--80aihfjcshcbin9q.co.uk./?q=Test%20URL-encoded%20stuff#fdfg',
                'one.two.three.xn--80aihfjcshcbin9q.co.uk.'
            ]
        ];
    }


    /**
     * Test 'getHostFromUrl' method.
     *
     * @param non-empty-string $url URL
     * @param non-empty-string $expected Expected result
     * @return void
     * @throws Exception
     */
    #[DataProvider('getHostFromUrlDataProvider')]
    final public function testGetHostFromUrl(string $url, string $expected): void
    {
        $this->assertEquals($expected, Http::getHostFromUrl($url));
    }


    /**
     * Test 'getHostFromUrl' method throws exception on empty URL.
     *
     * @return void
     * @throws Exception
     */
    final public function testGetHostFromUrlThrowsExceptionOnEmptyUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Http::getHostFromUrl('  ');
    }


    /**
     * Test 'getHostFromUrl' method throws exception on invalid URL.
     *
     * @return void
     * @throws Exception
     */
    final public function testGetHostFromUrlThrowsExceptionOnInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Http::getHostFromUrl('http://foo.bar?q=Spaces should be encoded');
    }


    /**
     * Test 'getHostFromUrl' method throws exception on invalid punycode URL.
     *
     * @return void
     * @throws Exception
     */
    final public function testGetHostFromUrlThrowsExceptionOnInvalidPunycodeUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Http::getHostFromUrl('https://one.two.three.xn--0aihfjcshcbin9q.co.uk./?q=Test%20URL-encoded%20stuff#fdfg');
    }


    /**
     * Data provider for 'testGetDomainFromUrl' method.
     *
     * @return array<string, array{0: non-empty-string, 1: null|non-empty-string}>
     */
    public static function getDomainFromUrlDataProvider(): array
    {
        return [
            'valid HTTP URL without slash at end' => ['http://foo.com/blah_blah', 'foo.com'],
            'valid HTTP URL with slash at end' => ['http://foo.com/blah_blah/', 'foo.com'],
            'valid HTTPS URL' => ['https://foo.com/blah_blah', 'foo.com'],
            'valid FTP URL' => ['ftp://foo.com/blah_blah', 'foo.com'],
            'valid URL without scheme' => ['//localhost.com/blah_blah', 'localhost.com'],
            'valid URL with username and port' => ['http://userid@example.com:8080', 'example.com'],
            'valid URL with query string' => ['http://example.com/path/to/file.html?query=string', 'example.com'],
            'valid URL with encoded query string' => ['http://foo.bar/?q=Test%20URL-encoded%20stuff', 'foo.bar'],
            'valid URL with unicode domain' => ['http://例子.测试', '例子.测试'],
            'valid URL with multiple domains' => [
                '//long.sub.domain.какой-то-сайт.уа/blah_blah',
                'какой-то-сайт.уа'
            ],
            'valid URL with punycode domain' => [
                'https://one.two.three.xn--80aihfjcshcbin9q.co.uk./?q=Test%20URL-encoded%20stuff#fdfg',
                'xn--80aihfjcshcbin9q.co.uk'
            ]
        ];
    }


    /**
     * Test 'getDomainFromUrl' method.
     *
     * @param non-empty-string $url URL
     * @param null|non-empty-string $expected Expected result
     * @return void
     * @throws Exception|CannotProcessHost
     */
    #[DataProvider('getDomainFromUrlDataProvider')]
    final public function testGetDomainFromUrl(string $url, null|string $expected): void
    {
        $this->assertEquals($expected, Http::getDomainFromUrl($url));
    }


    /**
     * Test 'getDomainFromUrl' method throws exception on empty URL.
     *
     * @return void
     * @throws Exception|CannotProcessHost
     */
    final public function testGetDomainFromUrlThrowsExceptionOnEmptyUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Http::getDomainFromUrl('  ');
    }


    /**
     * Test 'getDomainFromUrl' method throws exception on invalid URL.
     *
     * @return void
     * @throws Exception|CannotProcessHost
     */
    final public function testGetDomainFromUrlThrowsExceptionOnInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Http::getDomainFromUrl('http://foo.bar?q=Spaces should be encoded');
    }


    /**
     * Test 'getDomainFromUrl' method throws exception on invalid URL (with IP address).
     *
     * @return void
     * @throws Exception|CannotProcessHost
     */
    final public function testGetDomainFromUrlThrowsExceptionOnIpUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Http::getDomainFromUrl('http://142.42.1.1/path/to/file.html');
    }


    /**
     * Data provider for 'testIsValidDomain' method.
     *
     * @return array<string, array{0: non-empty-string, 1: bool, 2: bool}>
     */
    public static function isValidDomainDataProvider(): array
    {
        return [
            'empty string' => ['  ', false, false],
            'IP address' => ['192.168.1.1', false, false],
            'invalid domain without DNS check' => ['examplecom', false, false],
            'invalid domain with DNS check' => ['examplecom', true, false],
            'valid domain without DNS check' => ['aauu66bbdd.org', false, true],
            'valid domain with DNS check' => ['aauu66bbdd.org', true, false],
            'valid domain in punycode without DNS check' => ['xn--80aihfjcshcbin9q.xn--90ais', false, true],
            'valid domain in punycode with DNS check' => ['xn--80aihfjcshcbin9q.xn--90ais', true, true]
        ];
    }


    /**
     * Test 'isValidDomain' method.
     *
     * @param string $domain Domain name
     * @param bool $checkDns Whether to check DNS records
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isValidDomainDataProvider')]
    final public function testIsValidDomain(string $domain, bool $checkDns, bool $expected): void
    {
        $this->assertEquals($expected, Http::isValidDomain($domain, $checkDns));
    }


    /**
     * Data provider for 'testIsPunycodeDomain' method.
     *
     * @return array<string, array{0: non-empty-string, 1: bool}>
     */
    public static function isPunycodeDomainDataProvider(): array
    {
        return [
            'empty string' => ['  ', false],
            'not a domain' => ['some text 123', false],
            'regular domain' => ['example.com', false],
            'unicode domain' => ['例子.测试', false],
            'punycode domain' => ['xn--80aihfjcshcbin9q.xn--90ais', true]
        ];
    }


    /**
     * Test 'isPunycodeDomain' method.
     *
     * @param non-empty-string $domain Domain name
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isPunycodeDomainDataProvider')]
    final public function testIsPunycodeDomain(string $domain, bool $expected): void
    {
        $this->assertEquals($expected, Http::isPunycodeDomain($domain));
    }


    /**
     * Test 'isPunycodeDomain' method throws exception on invalid domain.
     *
     * @return void
     * @throws Exception|CannotProcessHost
     */
    final public function testIsPunycodeDomainThrowsExceptionOnInvalidPunycodeDomain(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Http::getDomainFromUrl('xn--0aihfjcshcbin9q.co.uk');
    }
}
