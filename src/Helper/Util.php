<?php

namespace Portknock\Helper;

class Util
{
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

    public static function die(int $code): never
    {
        http_response_code($code);
        die("{$code} NEIN!");
    }
}
