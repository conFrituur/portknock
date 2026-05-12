<?php

namespace Portknock\Tests\Controller;

use Portknock\Controller\Knock as KnockController;
use Portknock\Model\Allowlist;
use Portknock\Model\AllowlistEntry;
use Portknock\Model\Config;
use Portknock\Model\HttpHeaders;
use Portknock\Model\User;
use Portknock\Model\UserAccess;
use Portknock\Tests\Mock\MockException;

class KnockTest extends AbstractControllerTest
{
    public function testSuccessfulFirstKnockNoSecondKnock(): void
    {
        $expectedAllowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, null, self::REMOTE_ADDR_RANGE),
        ]);

        $this->prepSuccessfulKnock(new Allowlist([]), $expectedAllowList);

        // getRedirectHostUrl
        $this->configRepository->expects($this->once())
            ->method('getConfig')
            ->willReturn(new Config()); // empty config, so no redirect will be triggered

        $this->outputHandler->expects($this->once())
            ->method('echo')
            ->with('200 Added to allowlist');

        $this->getKnockController()->knock();
        self::assertTrue($this->logHandler->hasInfoThatMatches("/^first-knock successful, IPv6Range\=\[" . preg_quote(self::REMOTE_ADDR_RANGE, '/') . "\] has been written to the allowlist$/"));
    }

    public function testSuccessfulFirstKnockAgainOverwrite(): void
    {
        $headers                                  = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_REMOTE_ADDR] = self::IPv4;
        $this->headers                            = new HttpHeaders($headers);

        $previousAllowlist = new Allowlist([
            new AllowlistEntry(self::TEST_USER, null, self::REMOTE_ADDR_RANGE, self::TEST_AMENDKEY_HASH),
        ]);

        $expectedAllowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, self::IPv4, null),
        ]);

        $this->prepSuccessfulKnock($previousAllowlist, $expectedAllowList);

        // getRedirectHostUrl
        $this->configRepository->expects($this->once())
            ->method('getConfig')
            ->willReturn(new Config()); // empty config, so no redirect will be triggered

        $this->outputHandler->expects($this->once())
            ->method('echo')
            ->with('200 Added to allowlist');

        $this->getKnockController()->knock();
        self::assertTrue($this->logHandler->hasInfoThatMatches("/^first-knock successful, IPv4\=\[" . self::IPv4 . "\] has been written to the allowlist$/"));
    }

    public function testSuccessfulFirstKnockRedirectToSecondKnockToV6(): void
    {
        $headers                                  = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_REMOTE_ADDR] = self::IPv4;
        $this->headers                            = new HttpHeaders($headers);

        $expectedAllowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, self::IPv4, null, self::TEST_AMENDKEY_HASH),
        ]);

        $this->caseFirstKnockRedirect($expectedAllowList, AllowlistEntry::FIELD_IPV6);
    }

    public function testSuccessfulFirstKnockRedirectToSecondKnockToV4(): void
    {
        $headers                                  = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_REMOTE_ADDR] = self::IPv6;
        $this->headers                            = new HttpHeaders($headers);

        $expectedAllowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, null, self::IPv6Range, self::TEST_AMENDKEY_HASH),
        ]);

        $this->caseFirstKnockRedirect($expectedAllowList, AllowlistEntry::FIELD_IPV4);
    }

    public function testFirstKnockAlreadyAllowlisted(): void
    {
        $allowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, null, self::REMOTE_ADDR_RANGE),
        ]);

        $this->caseFirstKnockAlreadyAllowlisted($allowList);
    }

    public function testFirstKnockAlreadyAllowlistedBoth(): void
    {
        $allowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, self::IPv4, self::REMOTE_ADDR_RANGE),
        ]);

        $this->caseFirstKnockAlreadyAllowlisted($allowList);
    }

    public function testSuccessfulSecondKnock(): void
    {
        $this->headers = $this->getSecondKnockTestHeaders();

        $firstKnockEntry     = new AllowlistEntry(self::TEST_USER, null, self::REMOTE_ADDR_RANGE, self::TEST_AMENDKEY_HASH);
        $beginStateAllowlist = $this->getTestAllowlist();
        $beginStateAllowlist = $beginStateAllowlist->upsertEntry($firstKnockEntry);

        $secondKnockEntry  = new AllowlistEntry(self::TEST_USER, self::REMOTE_ADDR_IPv4, self::REMOTE_ADDR_RANGE);
        $expectedAllowList = $this->getTestAllowlist();
        $expectedAllowList = $expectedAllowList->upsertEntry($secondKnockEntry);

        $this->prepSuccessfulKnock($beginStateAllowlist, $expectedAllowList);

        // should not query if redirect is needed
        $this->configRepository->expects($this->never())
            ->method('getConfig');

        $this->outputHandler->expects($this->once())
            ->method('echo')
            ->with('200 Added to allowlist++');

        $this->getKnockController()->knock();
        self::assertTrue(
            $this->logHandler->hasInfoThatMatches(
                "/^second-knock successful, IPv4\=\[" . self::REMOTE_ADDR_IPv4 . "\] has been amended to " . self::TEST_USER . "'s AllowlistEntry/"
            )
        );
    }

    public function testSecondKnockKeyNotFound(): void
    {
        $this->headers = $this->getSecondKnockTestHeaders();

        $allowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, self::IPv4, self::REMOTE_ADDR_RANGE, self::TEST_AMENDKEY_HASH_2),
        ]);

        $this->prepAbortedKnock($allowList);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(403)
            ->will($this->throwException(new MockException('redirect -> die()')));

        $this->expectException(MockException::class);
        $this->getKnockController()->knock();
    }

    public function testSecondKnockNothingToAmend(): void
    {
        $this->headers = $this->getSecondKnockTestHeaders();

        $allowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, self::IPv4, self::REMOTE_ADDR_RANGE, self::TEST_AMENDKEY_HASH),
        ]);

        $this->prepAbortedKnock($allowList);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(409, 'Nothing to amend')
            ->will($this->throwException(new MockException('redirect -> die()')));

        $this->expectException(MockException::class);
        $this->getKnockController()->knock();
    }

    public function testSecondKnockWrongIpVersion(): void
    {
        $this->headers = $this->getSecondKnockTestHeaders();

        $allowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, self::REMOTE_ADDR_IPv4, null, self::TEST_AMENDKEY_HASH),
        ]);

        $this->prepAbortedKnock($allowList);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(409, 'Request from same IP version, expected ' . AllowlistEntry::FIELD_IPV6)
            ->will($this->throwException(new MockException('redirect -> die()')));

        $this->expectException(MockException::class);
        $this->getKnockController()->knock();
    }

    public function testMissingSesamHeader(): void
    {
        $this->prepMissingSesamHeader();
        $this->getKnockController()->knock();
    }

    public function testNoUserMatchForSesamHeader(): void
    {
        $this->prepNoUserMatchForSesamHeader();
        $this->getKnockController()->knock();
    }

    public function testUserIncorrectPermissions(): void
    {
        $user = new User(self::TEST_USER, UserAccess::READ_ONLY);
        $this->prepUserIncorrectPermissions($user);
        $this->getKnockController()->knock();
    }

    private function getKnockController(): KnockController
    {
        return new KnockController(
            $this->headers,
            $this->allowlistRepository,
            $this->userRepository,
            $this->keyRepository,
            $this->configRepository,
            $this->outputHandler
        );
    }

    private function prepSuccessfulKnock(Allowlist $getList, Allowlist $expectedAllowList): void
    {
        $this->prepGetAuthorizedUserFromHeaders();
        $this->allowlistRepository->expects($this->once())
            ->method('getList')
            ->willReturn($getList);

        $this->allowlistRepository->expects($this->once())
            ->method('save')
            ->with($expectedAllowList);
    }

    private function prepAbortedKnock(Allowlist $getList): void
    {
        $this->prepGetAuthorizedUserFromHeaders();
        $this->allowlistRepository->expects($this->once())
            ->method('getList')
            ->willReturn($getList);

        $this->allowlistRepository->expects($this->never())
            ->method('save');
    }

    private function prepGetAuthorizedUserFromHeaders(): void
    {
        $user = new User(self::TEST_USER, UserAccess::WRITE_ONLY);

        $this->keyRepository->expects($this->atLeastOnce())
            ->method('getKey')
            ->willReturn(self::TEST_KEY);

        $this->userRepository->expects($this->once())
            ->method('getUserByAuthHash')
            ->with(self::TEST_HASH)
            ->willReturn($user);
    }

    private function caseFirstKnockRedirect(Allowlist $expectedAllowList, string $expectedToIpVersion): void
    {
        $config = new Config(self::TEST_REDIRECT_V4, self::TEST_REDIRECT_V6);

        $this->prepSuccessfulKnock(new Allowlist([]), $expectedAllowList);

        //firstKnock
        $this->keyRepository->expects($this->once())
            ->method('generateRandomKey')
            ->willReturn(self::TEST_AMENDKEY);

        // getRedirectHostUrl
        $this->configRepository->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturn($config);

        $this->outputHandler->expects($this->once())
            ->method('redirect')
            ->with("https://{$expectedToIpVersion}-knock.example.nl/app/test/?amend=meer-sleutel")
            ->will($this->throwException(new MockException('redirect -> die()')));

        $this->expectException(MockException::class);
        $this->getKnockController()->knock();
    }

    private function caseFirstKnockAlreadyAllowlisted(Allowlist $allowList): void
    {
        $this->prepAbortedKnock($allowList);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(200, 'Already on allowlist')
            ->will($this->throwException(new MockException('redirect -> die()')));

        $this->expectException(MockException::class);
        $this->getKnockController()->knock();
    }
}
