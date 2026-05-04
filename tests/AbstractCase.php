<?php

namespace Portknock\Tests;

use Monolog\Formatter\LineFormatter;
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
    protected const string REMOTE_ADDR = "2a01:7c8:901:0:c01d:c0ff:ee:bad3";
    protected const string IPv4 = "37.97.254.1";
    protected const string IPv4_2 = "192.168.200.1";
    protected const string IPv4_3 = "80.69.69.100";
    protected const string IPv6 = "2a01:7c8:3:1337::1";
    protected const string IPv6Range = "2a01:7c8:3:1337::1/64";
    protected const string IPv6_2 = "fd::1";
    protected const string IPv6_3 = "2a02:26f0:1180:35::210:6ad4";
    protected const string TEST_USER = "Test";
    protected const string TEST_USER_2 = "Test2";
    protected const string TEST_USER_3 = "Test3";
    protected const string TEST_SESAM = "Code1";
    protected const string TEST_SESAM_2 = "Code2";
    protected const string TEST_SESAM_3 = "Code3";
    protected const string TEST_HASH = "78fa2f048f934d03a360f445e36740741c839487624c09e0ce249e1b55f6a72b"; // Code1
    protected const string TEST_HASH_2 = "9d98a31aceb2d2b651eddc419a975786499c6119ea617049dc462e72856194e0"; // Code2
    protected const string TEST_HASH_3 = "97f3986f35ca6b32fa132e9eb87fe94f0d3d0e339b07d20c73c7bfe1e33703aa"; // Code3
    protected const string TEST_KEY = "sleutel";

    protected TestHandler $logHandler;

    protected function setUp(): void
    {
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

    protected function getTestHeaders(): HttpHeaders
    {
        $rawHeaders = $this->getRawTestHeaders();
        return new HttpHeaders($rawHeaders);
    }

    protected function getTestAllowlist(): Allowlist
    {
        $allowlistEntries = [
            new AllowlistEntry(self::TEST_USER, self::IPv4, self::IPv6),
            new AllowlistEntry(self::TEST_USER_2, self::IPv4_2, self::IPv6_2),
            new AllowlistEntry(self::TEST_USER_3, self::IPv4_2, self::IPv6_3),
        ];
        return new Allowlist($allowlistEntries);
    }

    protected function getTestAllowlistJson(): string
    {
        return json_encode([
            self::TEST_USER   => [AllowlistEntry::FIELD_IPV4 => self::IPv4, AllowlistEntry::FIELD_IPV6 => self::IPv6],
            self::TEST_USER_2 => [AllowlistEntry::FIELD_IPV4 => self::IPv4_2, AllowlistEntry::FIELD_IPV6 => self::IPv6_2],
            self::TEST_USER_3 => [AllowlistEntry::FIELD_IPV4 => self::IPv4_2, AllowlistEntry::FIELD_IPV6 => self::IPv6_3],
        ]);
    }
}
