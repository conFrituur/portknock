<?php

namespace Portknock\Model;

use DomainException;
use Portknock\Helper\Util;

readonly class AllowlistEntry
{
    public const string FIELD_IPV4 = 'ipv4';
    public const string FIELD_IPV6 = 'ipv6';

    public function __construct(private string $userName, private ?string $ipv4Address, private ?string $ipv6Range)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (strlen($this->userName) < 3) {
            throw new DomainException("Username[={$this->userName}] must be at least 3 characters long");
        }

        if (!$this->ipv4Address && !$this->ipv6Range) {
            throw new DomainException("Must give at least one IPv4 or IPv6 range");
        }

        if ($this->ipv4Address && !Util::isValidIPv4($this->ipv4Address)) {
            throw new DomainException("IPv4 address[={$this->ipv4Address}] is invalid");
        }

        if ($this->ipv6Range && !Util::isValidIPv6Range($this->ipv6Range)) {
            throw new DomainException("IPv6 range[[={$this->ipv6Range}] is invalid");
        }
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getIpv4Address(): ?string
    {
        return $this->ipv4Address;
    }

    public function getIpv6Range(): ?string
    {
        return $this->ipv6Range;
    }

    /**
     * @return string[]
     */
    public function getIpArray(): array
    {
        $contents = [];

        if ($this->ipv4Address) {
            $contents[self::FIELD_IPV4] = $this->ipv4Address;
        }
        if ($this->ipv6Range) {
            $contents[self::FIELD_IPV6] = $this->ipv6Range;
        }

        return $contents;
    }

    public static function createFromAddress(string $userName, string $ipAddress): self
    {
        if (Util::isValidIPv4($ipAddress)) {
            return new self($userName, $ipAddress, null);
        }
        if (Util::isValidIPv6($ipAddress)) {
            $ipv6Range = Util::getRangeForIpv6Address($ipAddress);
            return new self($userName, null, $ipv6Range);
        }

        throw new DomainException("IpAddress[={$ipAddress}] is neither a valid IPv4 nor IPv6 address");
    }

    /**
     * @param string[][] $jsonData
     * @return AllowlistEntry[]
     */
    public static function fromJsonData(array $jsonData): array
    {
        /** @var AllowlistEntry[] $allowlistEntries */
        $allowlistEntries = [];
        foreach ($jsonData as $userName => $ipAddresses) {
            $ipv4Address        = $ipAddresses[self::FIELD_IPV4] ?? null;
            $ipv6Range          = $ipAddresses[self::FIELD_IPV6] ?? null;
            $allowlistEntries[] = new self($userName, $ipv4Address, $ipv6Range);
        }

        return $allowlistEntries;
    }

    public function getIpAddressAndRangeString(): string
    {
        $addresses = [];

        if ($this->getIpv4Address()) {
            $addresses[] = "IPv4=[{$this->getIpv4Address()}]";
        }
        if ($this->getIpv6Range()) {
            $addresses[] = "IPv6Range=[{$this->getIpv6Range()}]";
        }

        return implode(' & ', $addresses);
    }

    public function equals(self $other): bool
    {
        return $this->getUserName() === $other->getUserName()
            && $this->getIpv4Address() === $other->getIpv4Address()
            && $this->getIpv6Range() === $other->getIpv6Range();
    }
}
