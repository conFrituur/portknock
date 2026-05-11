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

    public function testGetMissingDataIpVersion(): void
    {
        $ipv4Only = new AllowlistEntry(self::TEST_USER, self::IPv4, null);
        $ipv6Only = new AllowlistEntry(self::TEST_USER, null, self::IPv6Range);
        $both = new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range);

        self::assertSame(AllowlistEntry::FIELD_IPV6, $ipv4Only->getMissingDataIpVersion());
        self::assertSame(AllowlistEntry::FIELD_IPV4, $ipv6Only->getMissingDataIpVersion());
        self::assertNull($both->getMissingDataIpVersion());
    }

    public function testCreateFromAddress(): void
    {
        $ipv4OnlyExpected = new AllowlistEntry(self::TEST_USER, self::IPv4, null);
        $ipv6OnlyExpected = new AllowlistEntry(self::TEST_USER, null, self::IPv6Range);

        $ipv4OnlyActual      = AllowlistEntry::createFromAddress(self::TEST_USER, self::IPv4);
        $ipv6OnlyActual      = AllowlistEntry::createFromAddress(self::TEST_USER, self::IPv6);
        $ipv6SameRangeActual = AllowlistEntry::createFromAddress(self::TEST_USER, self::IPv6_SAME_RANGE);

        self::assertEquals($ipv4OnlyExpected, $ipv4OnlyActual);
        self::assertEquals($ipv6OnlyExpected, $ipv6OnlyActual);
        self::assertEquals($ipv6OnlyExpected, $ipv6SameRangeActual);

        $this->expectException(DomainException::class);
        AllowlistEntry::createFromAddress(self::TEST_USER, 'lalala');
    }

    public function testAddAmendKey(): void
    {
        $entry   = new AllowlistEntry(self::TEST_USER, self::IPv4, null);
        self::assertNull($entry->getAmendKeyHash());

        $entry = $entry->addAmendKeyHash(self::TEST_HASH_2);
        self::assertSame(self::TEST_HASH_2, $entry->getAmendKeyHash());
    }

    public function testFromJsonData(): void
    {
        $allowlistEntryExpected = [new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range)];
        $allowlistEntryActual   = AllowlistEntry::fromJsonData(
            [self::TEST_USER => [AllowlistEntry::FIELD_IPV4 => self::IPv4, AllowlistEntry::FIELD_IPV6 => self::IPv6Range]]
        );

        self::assertEquals($allowlistEntryExpected, $allowlistEntryActual);
    }

    public function testGetIpArray(): void
    {
        $ipv4OnlyExpected = [AllowlistEntry::FIELD_IPV4 => self::IPv4];
        $ipv6OnlyExpected = [AllowlistEntry::FIELD_IPV6 => self::IPv6Range];
        $bothExpected     = [AllowlistEntry::FIELD_IPV4 => self::IPv4, AllowlistEntry::FIELD_IPV6 => self::IPv6Range];

        $ipv4OnlyActual = new AllowlistEntry(self::TEST_USER, self::IPv4, null);
        $ipv6OnlyActual = new AllowlistEntry(self::TEST_USER, null, self::IPv6Range);
        $bothActual     = new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range);

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
        $ipv6OnlyActual = new AllowlistEntry(self::TEST_USER, null, self::IPv6Range);
        $bothActual     = new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range);

        self::assertEquals($ipv4OnlyExpected, $ipv4OnlyActual->getIpAddressAndRangeString());
        self::assertEquals($ipv6OnlyExpected, $ipv6OnlyActual->getIpAddressAndRangeString());
        self::assertEquals($bothExpected, $bothActual->getIpAddressAndRangeString());
    }

    #[DataProvider('equalsAtLeastOneWithMissingIpVersionDataProvider')]
    public function testEqualsAtLeastOneWithMissingIpVersion(AllowlistEntry $listOne, AllowlistEntry $listTwo, bool $shouldEqual): void
    {
        self::assertSame($shouldEqual, $listOne->equalsAtLeastOneWithMissingIpVersion($listTwo));
    }

    public static function validateDataProvider(): array
    {
        return [
            [self::TEST_USER, self::IPv4, null, false],
            [self::TEST_USER, null, self::IPv6Range, false],
            [self::TEST_USER, null, self::IPv6, true],
            ['ta', null, self::IPv6Range, true],
            [self::TEST_USER, null, null, true],
            [self::TEST_USER, self::IPv6, null, true],
            [self::TEST_USER, self::IPv6Range, null, true],
            [self::TEST_USER, null, self::IPv4, true],
        ];
    }

    public static function equalsAtLeastOneWithMissingIpVersionDataProvider(): array
    {
        return [
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
                true,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
                new AllowlistEntry(self::TEST_USER, self::IPv4, null),
                true,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
                new AllowlistEntry(self::TEST_USER, null, self::IPv6Range),
                true,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
                new AllowlistEntry(self::TEST_USER, self::IPv4_2, null),
                false,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4_2, self::IPv6Range_2),
                new AllowlistEntry(self::TEST_USER, null, self::IPv6Range),
                false,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range_2),
                false,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
                new AllowlistEntry(self::TEST_USER, self::IPv4_2, self::IPv6Range),
                false,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
                new AllowlistEntry(self::TEST_USER, self::IPv4_2, self::IPv6Range_2),
                false,
            ],
            [
                new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
                new AllowlistEntry(self::TEST_USER_2, self::IPv4, self::IPv6Range),
                false,
            ],
        ];
    }
}
