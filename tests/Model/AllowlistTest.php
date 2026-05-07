<?php

namespace Portknock\Tests\Model;

use Portknock\Model\Allowlist;
use Portknock\Model\AllowlistEntry;
use Portknock\Tests\AbstractCase;

class AllowlistTest extends AbstractCase
{
    public function testImportFromJson(): void
    {
        $expectedAllowlist = $this->getTestAllowlist();
        $allowlistFromJson = Allowlist::fromJson($this->getTestAllowlistJson());
        self::assertEquals($expectedAllowlist, $allowlistFromJson);
    }

    public function testToJson(): void
    {
        $expectedJson = $this->getTestAllowlistJson();
        $actualJson   = $this->getTestAllowlist()->toJson();
        self::assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }

    public function testToArrayOfIps(): void
    {
        // should still contain duplicates at this point
        $expectedArrayOfIps = [
            self::IPv4,
            self::IPv6Range,
            self::IPv4_2,
            self::IPv6Range_2,
            self::IPv4_2,
            self::IPv6Range_3,
        ];
        $actualIpList       = $this->getTestAllowlist()->toArrayOfIpsAndRanges();
        self::assertEquals($expectedArrayOfIps, $actualIpList);
    }

    public function testHasEntryInList(): void
    {
        $allowlist = $this->getTestAllowlist();
        $entries   = $allowlist->getAllowlistEntries();
        shuffle($entries);
        self::assertTrue($allowlist->hasEntryInList(array_pop($entries)));
    }

    public function testHasEntryNotInList(): void
    {
        $allowlist = $this->getTestAllowlist();
        $entry     = new AllowlistEntry(self::TEST_USER, self::IPv4_3, null);
        self::assertFalse($allowlist->hasEntryInList($entry));
    }

    public function testUpsert(): void
    {
        $sameEntry   = new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6);
        $updateEntry = new AllowlistEntry(self::TEST_USER_2, self::IPv4_3, null);
        $newEntry    = new AllowlistEntry('new', null, '2600:9000:20ab:9000:f:425b:9b80:93a1');

        $expectedAllowlist = new Allowlist([
            new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6),
            $updateEntry,
            new AllowlistEntry(self::TEST_USER_3, self::IPv4_2, self::IPv6_3),
            $newEntry,
        ]);

        $actualAllowlist   = $this->getTestAllowlist();
        $actualAllowlist   = $actualAllowlist->upsertEntry($sameEntry);
        $actualAllowlist   = $actualAllowlist->upsertEntry($updateEntry);
        $actualAllowlist   = $actualAllowlist->upsertEntry($newEntry);

        self::assertEquals($expectedAllowlist, $actualAllowlist);
    }
}
