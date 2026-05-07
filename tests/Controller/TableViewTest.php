<?php

namespace Portknock\Tests\Controller;

use Portknock\Controller\TableView as TableViewController;
use Portknock\Model\User;
use Portknock\Model\UserAccess;

class TableViewTest extends AbstractControllerTest
{
    public function testShowList()
    {
        $expectedOutput = <<<EOD
            37.97.254.1
            2a01:7c8:3:1337::/64
            192.168.200.1
            fd::/64
            2a02:26f0:1180:35::/64
            EOD;

        $user      = new User(self::TEST_USER, UserAccess::READ_ONLY);
        $allowList = $this->getTestAllowlist();

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

        $this->getTableViewController()->showList();
    }
    public function testMissingSesamHeader()
    {
        $this->prepMissingSesamHeader();
        $this->getTableViewController()->showList();
    }

    public function testNoUserMatchForSesamHeader()
    {
        $this->prepNoUserMatchForSesamHeader();
        $this->getTableViewController()->showList();
    }

    public function testUserIncorrectPermissions()
    {
        $user    = new User(self::TEST_USER, UserAccess::WRITE_ONLY);
        $this->prepUserIncorrectPermissions($user);
        $this->getTableViewController()->showList();
    }

    private function getTableViewController(): TableViewController
    {
        return new TableViewController($this->headers, $this->allowlistRepository, $this->userRepository, $this->keyRepository, $this->outputHandler);
    }
}
