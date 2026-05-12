<?php

namespace Portknock\Tests;

use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Portknock\Helper\Log;
use Portknock\Model\Allowlist;
use Portknock\Model\AllowlistEntry;
use Portknock\Model\HttpHeaders;

abstract class AbstractCase extends TestCase
{
    protected const string REMOTE_ADDR_IPv4 = "80.69.65.9";
    protected const string REMOTE_ADDR_IPv6 = "2a01:7c8:901:0:c01d:c0ff:ee:bad3";
    protected const string REMOTE_ADDR_RANGE = "2a01:7c8:901::/64";
    protected const string IPv4 = "37.97.254.1";
    protected const string IPv4_2 = "192.168.200.1";
    protected const string IPv4_3 = "80.69.69.100";
    protected const string IPv6 = "2a01:7c8:3:1337::1";
    protected const string IPv6_SAME_RANGE = "2a01:7c8:3:1337:cf4d:1c0c:3f19:6bed";
    protected const string IPv6Range = "2a01:7c8:3:1337::/64";
    protected const string IPv6_2 = "fd::1";
    protected const string IPv6Range_2 = "fd::/64";
    protected const string IPv6_3 = "2a02:26f0:1180:35::210:6ad4";
    protected const string IPv6Range_3 = "2a02:26f0:1180:35::/64";
    protected const string TEST_USER = "Test";
    protected const string TEST_USER_2 = "Test2";
    protected const string TEST_USER_3 = "Test3";
    protected const string TEST_USER_4 = "Test4";
    protected const string TEST_SESAM = "Code1";
    protected const string TEST_SESAM_2 = "Code2";
    protected const string TEST_SESAM_3 = "Code3";
    protected const string TEST_HASH = "78fa2f048f934d03a360f445e36740741c839487624c09e0ce249e1b55f6a72b"; // Code1
    protected const string TEST_HASH_2 = "9d98a31aceb2d2b651eddc419a975786499c6119ea617049dc462e72856194e0"; // Code2
    protected const string TEST_HASH_3 = "97f3986f35ca6b32fa132e9eb87fe94f0d3d0e339b07d20c73c7bfe1e33703aa"; // Code3
    protected const string TEST_KEY = "sleutel";
    protected const string TEST_AMENDKEY = "meer-sleutel";
    protected const string TEST_AMENDKEY_2 = "amour-sleutel";
    protected const string TEST_AMENDKEY_HASH = "c1b3f44eb3c09311b605a3bac8457f8695ba02a8ee626af1391503e732153756";
    protected const string TEST_AMENDKEY_HASH_2 = "4a76b580287a175abddf3d31848e635f367425caec434cb15967b0ce12230db5";
    protected const string TEST_REDIRECT_V4 = "ipv4-knock.example.nl";
    protected const string TEST_REDIRECT_V6 = "ipv6-knock.example.nl";

    protected TestHandler $logHandler;

    protected function setUp(): void
    {
        putenv('LOG_LEVEL'); // unset the env level from potential earlier tests
        $this->logHandler = new TestHandler();
        $log              = new Logger(__CLASS__, [$this->logHandler]);
        Log::setLogger($log);
        Log::setPersistentContext([]); // clear context from potential earlier tests
    }

    protected function debugLog(): void
    {
        $log = new Logger(__CLASS__);
        $log->pushHandler(new StreamHandler('php://stdout', Level::Debug));
        Log::addLogger($log);
    }

    protected function getRawTestHeaders(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/Fixtures/headers.json'), true);
    }

    protected function getRawSecondKnockTestHeaders(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/Fixtures/headers-second-knock.json'), true);
    }

    protected function getTestHeaders(): HttpHeaders
    {
        $rawHeaders = $this->getRawTestHeaders();
        return new HttpHeaders($rawHeaders);
    }

    protected function getSecondKnockTestHeaders(): HttpHeaders
    {
        $rawHeaders = $this->getRawSecondKnockTestHeaders();
        return new HttpHeaders($rawHeaders);
    }

    protected function getTestAllowlist(): Allowlist
    {
        $allowlistEntries = [
            new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6Range),
            new AllowlistEntry(self::TEST_USER_2, self::IPv4_2, self::IPv6Range_2),
            new AllowlistEntry(self::TEST_USER_3, self::IPv4_2, null),
            new AllowlistEntry(self::TEST_USER_4, null, self::IPv6Range_3, self::TEST_AMENDKEY_HASH_2),
        ];
        return new Allowlist($allowlistEntries);
    }

    protected function getTestAllowlistJson(): string
    {
        return json_encode([
            self::TEST_USER   => [AllowlistEntry::FIELD_IPV4 => self::IPv4, AllowlistEntry::FIELD_IPV6 => self::IPv6Range],
            self::TEST_USER_2 => [AllowlistEntry::FIELD_IPV4 => self::IPv4_2, AllowlistEntry::FIELD_IPV6 => self::IPv6Range_2],
            self::TEST_USER_3 => [AllowlistEntry::FIELD_IPV4 => self::IPv4_2],
            self::TEST_USER_4 => [AllowlistEntry::FIELD_IPV6 => self::IPv6Range_3, AllowlistEntry::FIELD_AMENDKEY => self::TEST_AMENDKEY_HASH_2],
        ], JSON_PRETTY_PRINT);
    }
}
