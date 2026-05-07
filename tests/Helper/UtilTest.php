<?php

namespace Portknock\Tests\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use Portknock\Helper\Util;
use Portknock\Tests\AbstractCase;
use RuntimeException;

class UtilTest extends AbstractCase
{
    #[DataProvider('ipv4DataProvider')]
    public function testValidIpv4(string $ip, bool $isValid): void
    {
        static::assertSame(Util::isValidIPv4($ip), $isValid);
    }

    #[DataProvider('ipv6DataProvider')]
    public function testValidIpv6(string $ip, bool $isValid): void
    {
        static::assertSame(Util::isValidIPv6($ip), $isValid);
    }

    public function testHash(): void
    {
        $hash = Util::hash('hetlaatstelevel', 'sleutel');
        static::assertSame('f677518b4fe0b8f556fe60131e4244dd9961718d6fbbf219fbd5a98a2412907b', $hash);
    }

    #[DataProvider('ipv6ToRangeDataProvider')]
    public function testGetRangeForIpv6Address(string $ipAddress, ?string $expectedRange): void
    {

        if (!$expectedRange) {
            $this->expectException(RuntimeException::class);
        }

        $actualRange = Util::getRangeForIpv6Address($ipAddress);

        static::assertSame($expectedRange, $actualRange);
    }

    public function testGetRangeForIpv6AddressOtherSubnet(): void
    {
        $actualRange = Util::getRangeForIpv6Address('2001:db8:85a3::8a2e:370:7334', 32);
        static::assertSame('2001:db8::/32', $actualRange);
    }

    public static function ipv4DataProvider(): array
    {
        return [
            ['192.168.0.1', true],
            ['0.0.0.0', false],
            ['255.255.255.255', false],
            ['127.0.0.1', false],
            ['10.0.0.1', true],
            ['9.9.9.9', true],
            ['80.69.69.100', true],
            ['80.69.69.0/24', false],
            ['uhr3287fewjkb3r28y9', false],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', false],
            ['2001:db8:85a3::8a2e:370:7334', false],
            ['fe80::1ff:fe23:4567:890a', false],
        ];
    }

    public static function ipv6DataProvider(): array
    {
        return [
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', true],
            ['2001:db8:85a3::8a2e:370:7334', true],
            ['2a01:7c8:3:1337::1', true],
            ['fe80::1ff:fe23:4567:890a', false],
            ['fe80::1', false],
            ['fd0d::1', true],
            ['80.69.69.100', false],
            ['80.69.69.0/24', false],
            ['uhr3287fewjkb3r28y9', false],
        ];
    }

    public static function ipv6ToRangeDataProvider(): array
    {
        return [
            [self::IPv6, self::IPv6Range],
            ['2001:db8:85a3::8a2e:370:7334', '2001:db8:85a3::/64'],
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:db8:85a3::/64'],
            ['2a02:a119:8fa2:c5c4:1112:1b08:94b1:fcf', '2a02:a119:8fa2:c5c4::/64'],
            ['2a02:a119:8fa2:c5c4:1112::b33f', '2a02:a119:8fa2:c5c4::/64'],
            ['fd0d::1', 'fd0d::/64'],
            ['fe80::1', null],
            ['80.69.69.100', null],
        ];
    }
}
