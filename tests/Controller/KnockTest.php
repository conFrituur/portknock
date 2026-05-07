<?php

namespace Portknock\Tests\Controller;

use Portknock\Controller\Knock as KnockController;
use Portknock\Model\Allowlist;
use Portknock\Model\AllowlistEntry;
use Portknock\Model\User;
use Portknock\Model\UserAccess;

class KnockTest extends AbstractControllerTest
{
    public function testSuccessfulKnock()
    {
        $user              = new User(self::TEST_USER, UserAccess::WRITE_ONLY);
        $emptyAllowList    = new Allowlist([]);
        $expectedAllowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, null, self::REMOTE_ADDR_RANGE),
        ]);

        // getRemoteAddressFromHeaders - no mock required
        // getAuthorizedUserFromHeaders

        $this->keyRepository->expects($this->once())
            ->method('getKey')
            ->willReturn(self::TEST_KEY);

        $this->userRepository->expects($this->once())
            ->method('getUserByAuthHash')
            ->with(self::TEST_HASH)
            ->willReturn($user);

        // upsertEntryToAllowlist

        $this->allowlistRepository->expects($this->once())
            ->method('getList')
            ->willReturn($emptyAllowList);

        $this->allowlistRepository->expects($this->once())
            ->method('save')
            ->with($expectedAllowList);

        $this->outputHandler->expects($this->once())
            ->method('echo')
            ->with('200 Added to allowlist');

        $this->getKnockController()->knock();
        self::assertTrue($this->logHandler->hasInfoThatContains("has been added to the allowlist"));
    }

    public function testSuccessfulKnockAlreadyAllowlisted()
    {
        $user      = new User(self::TEST_USER, UserAccess::WRITE_ONLY);
        $allowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, null, self::REMOTE_ADDR_RANGE),
        ]);

        $this->keyRepository->expects($this->once())
            ->method('getKey')
            ->willReturn(self::TEST_KEY);

        $this->userRepository->expects($this->once())
            ->method('getUserByAuthHash')
            ->with(self::TEST_HASH)
            ->willReturn($user);

        $this->allowlistRepository->expects($this->once())
            ->method('getList')
            ->willReturn($allowList);

        $this->allowlistRepository->expects($this->never())
            ->method('save');

        $this->outputHandler->expects($this->once())
            ->method('echo')
            ->with('200 Already in allowlist');

        $this->getKnockController()->knock();
        self::assertTrue($this->logHandler->hasDebugThatContains("already allowlisted"));
    }

    public function testMissingSesamHeader()
    {
        $this->prepMissingSesamHeader();
        $this->getKnockController()->knock();
    }

    public function testNoUserMatchForSesamHeader()
    {
        $this->prepNoUserMatchForSesamHeader();
        $this->getKnockController()->knock();
    }

    public function testUserIncorrectPermissions()
    {
        $user    = new User(self::TEST_USER, UserAccess::READ_ONLY);
        $this->prepUserIncorrectPermissions($user);
        $this->getKnockController()->knock();
    }

    private function getKnockController(): KnockController
    {
        return new KnockController($this->headers, $this->allowlistRepository, $this->userRepository, $this->keyRepository, $this->outputHandler);
    }
}
