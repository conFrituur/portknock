<?php

namespace Portknock\Helper;

use IPLib\Range\Subnet;
use IPLib\Factory;
use Random\RandomException;
use RuntimeException;

class Util
{
    /**
     * @param int<1, max> $length
     * @throws RandomException
     */
    public static function generateRandomString(int $length): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function hash(string $data, string $key): string
    {
        return hash_hmac('sha256', $data, $key);
    }

    public static function isValidIPv4(string $ip): bool
    {
        $opts = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE;
        return filter_var($ip, FILTER_VALIDATE_IP, $opts) !== false;
    }

    public static function isValidIPv6(string $ip): bool
    {
        $opts = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE;
        return filter_var($ip, FILTER_VALIDATE_IP, $opts) !== false;
    }

    public static function isValidIPv6Range(string $range): bool
    {
        $ipRange = Factory::parseRangeString($range);

        if ($ipRange instanceof Subnet) {
            return Util::isValidIPv6($ipRange->getStartAddress());
        }

        return false;
    }

    /**
     * Prefix length of 64 is assumed, as this is most likely used with SLAAC
     *
     * @param string $ipAddress
     * @param int    $prefixLength
     * @return string
     */
    public static function getRangeForIpv6Address(string $ipAddress, int $prefixLength = 64): string
    {
        if (!self::isValidIPv6($ipAddress)) {
            throw new RuntimeException("IP[=$ipAddress] is not a valid IPv6 address");
        }

        /** @var Subnet $ipRange */
        $ipRange = Factory::parseRangeString("$ipAddress/$prefixLength");

        return $ipRange->getStartAddress() . "/" . $ipRange->getNetworkPrefix();
    }
}
