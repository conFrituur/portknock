<?php

namespace Portknock\Model;

readonly class Allowlist
{
    /**
     * @param AllowlistEntry[] $allowlistEntries
     */
    public function __construct(private array $allowlistEntries) {}

    /**
     * @return AllowlistEntry[]
     */
    public function getAllowlistEntries(): array
    {
        return $this->allowlistEntries;
    }

    public function getAllowlistEntryByUserNameAmendKey(string $userName, string $amendKeyHash): ?AllowlistEntry
    {
        return array_find(
            $this->allowlistEntries,
            fn (AllowlistEntry $allowlistEntry) => $allowlistEntry->getUserName() === $userName && $allowlistEntry->getAmendKeyHash() === $amendKeyHash
        );
    }

    public static function fromJson(string $encodedEntries): self
    {
        $jsonData = json_decode($encodedEntries, true, flags: JSON_THROW_ON_ERROR);
        $allowlistEntries = AllowlistEntry::fromJsonData($jsonData);
        return new self($allowlistEntries);
    }

    public function toJson(): string
    {
        $entries = [];
        foreach ($this->allowlistEntries as $allowlistEntry) {
            $entries[$allowlistEntry->getUserName()] = $allowlistEntry->toJsonData();
        }

        return json_encode($entries, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @return string[]
     */
    public function toArrayOfIpsAndRanges(): array
    {
        $ipEntries = [];
        foreach ($this->allowlistEntries as $allowlistEntry) {
            foreach ($allowlistEntry->getIpArray() as $allowlistIp) {
                $ipEntries[] = $allowlistIp;
            }
        }
        return $ipEntries;
    }

    public function hasEntryInList(AllowlistEntry $allowlistEntryToCheck): bool
    {
        return array_any(
            $this->getAllowlistEntries(),
            fn (AllowlistEntry $allowlistEntry) => $allowlistEntry->equalsAtLeastOneWithMissingIpVersion($allowlistEntryToCheck)
        );
    }

    public function hasIpAddressInList(string $ipToCheck): bool
    {
        return array_any(
            $this->getAllowlistEntries(),
            fn (AllowlistEntry $allowlistEntry) => $allowlistEntry->hasIpAddressInEntry($ipToCheck)
        );
    }

    public function upsertEntry(AllowlistEntry $newEntry): self
    {
        $allowlistEntries = $this->allowlistEntries;

        $updated = false;

        foreach ($allowlistEntries as $key => $allowlistEntry) {
            if ($allowlistEntry->getUserName() == $newEntry->getUserName()) {
                $allowlistEntries[$key] = $newEntry;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $allowlistEntries[] = $newEntry;
        }

        return new self($allowlistEntries);
    }
}
