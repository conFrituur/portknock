<?php

namespace Controller;

use Portknock\Controller\CheckView as CheckViewController;
use Portknock\Model\User;
use Portknock\Model\UserAccess;
use Portknock\Tests\Controller\AbstractControllerTest;
use Portknock\Tests\Mock\MockException;

class CheckViewTest extends AbstractControllerTest
{
    public function testIsIpOnAllowlist()
    {
        $_POST['ip'] = self::IPv4;
        $isAllowlisted = true;

        $this->caseIsIpOnAllowlist($isAllowlisted);
    }

    public function testIsIpOnAllowlistNope()
    {
        $_POST['ip'] = self::IPv4_3;
        $isAllowlisted = false;

        $this->caseIsIpOnAllowlist($isAllowlisted);
    }

    public function testIsIpOnAllowlistNonsense()
    {
        $_POST['ip'] = 'lalala';
        $user          = new User(self::TEST_USER, UserAccess::READ_ONLY);

        $this->keyRepository->expects($this->once())
            ->method('getKey')
            ->willReturn(self::TEST_KEY);

        $this->userRepository->expects($this->once())
            ->method('getUserByAuthHash')
            ->with(self::TEST_HASH)
            ->willReturn($user);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(400)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);

        $this->getCheckViewController()->isIpOnAllowlist();
    }

    public function testMissingSesamHeader()
    {
        $this->prepMissingSesamHeader();
        $this->getCheckViewController()->isIpOnAllowlist();
    }

    public function testNoUserMatchForSesamHeader()
    {
        $this->prepNoUserMatchForSesamHeader();
        $this->getCheckViewController()->isIpOnAllowlist();
    }

    public function testUserIncorrectPermissions()
    {
        $user    = new User(self::TEST_USER, UserAccess::WRITE_ONLY);
        $this->prepUserIncorrectPermissions($user);
        $this->getCheckViewController()->isIpOnAllowlist();
    }

    private function getCheckViewController(): CheckViewController
    {
        return new CheckViewController($this->headers, $this->allowlistRepository, $this->userRepository, $this->keyRepository, $this->configRepository, $this->outputHandler);
    }

    private function caseIsIpOnAllowlist(bool $isAllowlisted): void
    {
        $expectedOutput = json_encode(['isAllowlisted' => $isAllowlisted]);
        $user          = new User(self::TEST_USER, UserAccess::READ_ONLY);
        $allowList     = $this->getTestAllowlist();

        // getAuthorizedUserFromHeaders
        $this->keyRepository->expects($this->once())
            ->method('getKey')
            ->willReturn(self::TEST_KEY);

        $this->userRepository->expects($this->once())
            ->method('getUserByAuthHash')
            ->with(self::TEST_HASH)
            ->willReturn($user);

        // getAllowedIps
        $this->allowlistRepository->expects($this->once())
            ->method('getList')
            ->willReturn($allowList);

        // output
        $this->outputHandler->expects($this->once())
            ->method('echo')
            ->with($expectedOutput);

        $this->getCheckViewController()->isIpOnAllowlist();
    }
}
