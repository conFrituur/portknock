<?php

namespace Portknock\Tests\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use Portknock\Model\HttpHeaders;
use Portknock\Tests\AbstractCase;

class HttpHeadersTest extends AbstractCase
{
    public function testGetRemoteAddr(): void
    {
        $headers = $this->getTestHeaders();
        self::assertSame(self::REMOTE_ADDR, $headers->getRemoteAddr());
    }

    public function testGetSesamHeader(): void
    {
        $headers = $this->getTestHeaders();
        self::assertSame(self::TEST_SESAM, $headers->getSesam());
    }

    #[DataProvider('validateDataProvider')]
    public function testGetRoutingUri(string $requestUri, string $expectedOutputUri): void
    {
        $headers = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_REQUEST_URI] = $requestUri;
        $httpHeaders = new HttpHeaders($headers);

        self::assertSame($expectedOutputUri, $httpHeaders->getRoutingUri());
    }

    public function testGetRoutingUriMissingRequestUriHeader(): void
    {
        $headers = $this->getRawTestHeaders();
        unset($headers[HttpHeaders::HEADER_REQUEST_URI]);
        $httpHeaders = new HttpHeaders($headers);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HttpHeader is missing REQUEST_URI or PHP_SELF');
        $httpHeaders->getRoutingUri();
    }

    public function testGetRoutingUriMissingPhpSelfHeader(): void
    {
        $headers = $this->getRawTestHeaders();
        unset($headers[HttpHeaders::HEADER_PHP_SELF]);
        $httpHeaders = new HttpHeaders($headers);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HttpHeader is missing REQUEST_URI or PHP_SELF');
        $httpHeaders->getRoutingUri();
    }

    public function testGetRoutingUriInvalidError(): void
    {
        $headers = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_REQUEST_URI] = 'http:///test';
        $httpHeaders = new HttpHeaders($headers);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('REQUEST_URI is not a valid URI[=http:///test]');
        $httpHeaders->getRoutingUri();
    }

    public function testGetRoutingUriPathDidntStartWithError(): void
    {
        $headers = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_REQUEST_URI] = 'lalal/app/test/view';
        $httpHeaders = new HttpHeaders($headers);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Uri[=lalal/app/test/view] should start with[=/app/test]');
        $httpHeaders->getRoutingUri();
    }

    public static function validateDataProvider(): array
    {
        return [
            ['/app/test', ''],
            ['/app/test/', ''],
            ['/app/test/index.php', ''],
            ['/app/test/index.php?foo=bar', ''],
            ['/app/test/?foo=bar', ''],
            ['/app/test?foo=bar', ''],
            ['/app/test/view', 'view'],
            ['/app/test/view/', 'view'],
            ['/app/test/view?foo=bar', 'view'],
            ['/app/test/view/?foo=bar', 'view'],
        ];
    }
}
