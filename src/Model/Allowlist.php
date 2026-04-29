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

    public static function fromJsonEncodedString(string $encodedEntries): self
    {
        $jsonData = json_decode($encodedEntries, true, flags: JSON_THROW_ON_ERROR);
        $allowlistEntries = AllowlistEntry::fromJsonData($jsonData);
        return new self($allowlistEntries);
    }

    public function toJsonEncodedString(): string
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
    public function toArrayOfIps(): array
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
            fn ($allowlistEntry) => $allowlistEntry->equals($allowlistEntryToCheck)
        );
    }

    public function upsertEntry(AllowlistEntry $newEntry): self
    {
        $allowlistEntries = $this->allowlistEntries;

        $updated = false;

        foreach ($allowlistEntries as $key => $allowlistEntry) {
            if ($allowlistEntry->equals($newEntry)) {
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
