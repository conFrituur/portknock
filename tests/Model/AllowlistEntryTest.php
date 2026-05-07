<?php

namespace Portknock\Tests\Model;

use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use Portknock\Model\AllowlistEntry;
use Portknock\Tests\AbstractCase;

class AllowlistEntryTest extends AbstractCase
{
    #[DataProvider('validateDataProvider')]
    public function testValidate(string $user, ?string $ipv4, ?string $ipv6, bool $expectException): void
    {
        if ($expectException) {
            $this->expectException(DomainException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }
        new AllowlistEntry($user, $ipv4, $ipv6);
    }

    public function testCreate(): void
    {
        $ipv4OnlyExpected = new AllowlistEntry(self::TEST_USER, self::IPv4, null);
        $ipv6OnlyExpected = new AllowlistEntry(self::TEST_USER, null, self::IPv6);

        $ipv4OnlyActual   = AllowlistEntry::create(self::TEST_USER, self::IPv4);
        $ipv6OnlyActual   = AllowlistEntry::create(self::TEST_USER, self::IPv6);

        self::assertEquals($ipv4OnlyExpected, $ipv4OnlyActual);
        self::assertEquals($ipv6OnlyExpected, $ipv6OnlyActual);

        $this->expectException(DomainException::class);
        AllowlistEntry::create(self::TEST_USER, 'lalala');
    }

    public function testFromJsonData(): void
    {
        $allowlistEntryExpected = [new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6)];
        $allowlistEntryActual   = AllowlistEntry::fromJsonData(
            [self::TEST_USER => [AllowlistEntry::FIELD_IPV4 => self::IPv4, AllowlistEntry::FIELD_IPV6 => self::IPv6]]
        );

        self::assertEquals($allowlistEntryExpected, $allowlistEntryActual);
    }

    public function testGetIpArray(): void
    {
        $ipv4OnlyExpected = [AllowlistEntry::FIELD_IPV4 => self::IPv4];
        $ipv6OnlyExpected = [AllowlistEntry::FIELD_IPV6 => self::IPv6];
        $bothExpected     = [AllowlistEntry::FIELD_IPV4 => self::IPv4, AllowlistEntry::FIELD_IPV6 => self::IPv6];

        $ipv4OnlyActual = new AllowlistEntry(self::TEST_USER, self::IPv4, null);
        $ipv6OnlyActual = new AllowlistEntry(self::TEST_USER, null, self::IPv6);
        $bothActual     = new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6);

        self::assertEquals($ipv4OnlyExpected, $ipv4OnlyActual->getIpArray());
        self::assertEquals($ipv6OnlyExpected, $ipv6OnlyActual->getIpArray());
        self::assertEquals($bothExpected, $bothActual->getIpArray());
    }

    public function testGetIpAddressesString(): void
    {
        $ipv4OnlyExpected = "IPv4=[" . self::IPv4 . "]";
        $ipv6OnlyExpected = "IPv6Range=[" . self::IPv6Range . "]";
        $bothExpected     = "IPv4=[" . self::IPv4 . "] & IPv6Range=[" . self::IPv6Range . "]";

        $ipv4OnlyActual = new AllowlistEntry(self::TEST_USER, self::IPv4, null);
        $ipv6OnlyActual = new AllowlistEntry(self::TEST_USER, null, self::IPv6);
        $bothActual     = new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6);

        self::assertEquals($ipv4OnlyExpected, $ipv4OnlyActual->getIpAddressAndRangeString());
        self::assertEquals($ipv6OnlyExpected, $ipv6OnlyActual->getIpAddressAndRangeString());
        self::assertEquals($bothExpected, $bothActual->getIpAddressAndRangeString());
    }

    #[DataProvider('equalsDataProvider')]
    public function testEquals(AllowlistEntry $listOne, AllowlistEntry $listTwo, bool $shouldEqual): void
    {
        self::assertEquals($shouldEqual, $listOne->equals($listTwo));
    }

    public static function validateDataProvider(): array
    {
        return [
            [self::TEST_USER, self::IPv4, null, false],
            [self::TEST_USER, null, self::IPv6, false],
            ['ta', null, self::IPv6, true],
            [self::TEST_USER, null, null, true],
            [self::TEST_USER, self::IPv6, null, true],
            [self::TEST_USER, null, self::IPv4, true],
        ];
    }

    public static function equalsDataProvider(): array
    {
        return [
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6),
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6),
                true,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6),
                new AllowlistEntry(self::TEST_USER, self::IPv4, null),
                false,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6),
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6_2),
                false,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6),
                new AllowlistEntry(self::TEST_USER, self::IPv4_2, self::IPv6),
                false,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6),
                new AllowlistEntry(self::TEST_USER_2, self::IPv4, self::IPv6),
                false,
            ],
        ];
    }
}
