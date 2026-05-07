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
            $entries[$allowlistEntry->getUserName()] = $allowlistEntry->getIpArray();
        }

        return json_encode($entries, JSON_THROW_ON_ERROR);
    }

    /**
     * @return string[]
     */
    public function toArrayOfIpsAndRanges(): array
    {
        $ipEntries = [];
        foreach ($this->allowlistEntries as $allowlistEntry) {
            foreach ($allowlistEntry->getIpAndRangeArray() as $allowlistIp) {
                $ipEntries[] = $allowlistIp;
            }
        }
        return $ipEntries;
    }

    public function hasEntryInList(AllowlistEntry $allowlistEntryToCheck): bool
    {
        return array_any(
            $this->getAllowlistEntries(),
            fn (AllowlistEntry $allowlistEntry) => $allowlistEntry->equals($allowlistEntryToCheck)
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
            }
        }

        if (!$updated) {
            $allowlistEntries[] = $newEntry;
        }

        return new self($allowlistEntries);
    }
}
