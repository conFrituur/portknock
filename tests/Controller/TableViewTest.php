<?php

namespace Controller;

use Portknock\Controller\TableView;
use Portknock\Helper\ExitHandler;
use Portknock\Model\Allowlist;
use Portknock\Model\AllowlistEntry;
use Portknock\Model\HttpHeaders;
use Portknock\Model\User;
use Portknock\Model\UserAccess;
use Portknock\Repository\AllowlistRepository;
use Portknock\Repository\KeyRepository;
use Portknock\Repository\UserRepository;
use Portknock\Tests\AbstractCase;
use Portknock\Tests\Mock\MockException;

class TableViewTest extends AbstractCase
{
    private TableView $tableViewController;
    private AllowlistRepository $allowlistRepository;
    private UserRepository $userRepository;
    private KeyRepository $keyRepository;
    private ExitHandler $exitHandler;

    protected function setUp(): void
    {
        $this->allowlistRepository = $this->createMock(AllowlistRepository::class);
        $this->userRepository      = $this->createMock(UserRepository::class);
        $this->keyRepository       = $this->createMock(KeyRepository::class);
        $this->exitHandler         = $this->createMock(ExitHandler::class);
        $this->tableViewController = new TableView($this->allowlistRepository, $this->userRepository, $this->keyRepository, $this->exitHandler);
    }

    public function testShowList()
    {
        $expectedOutput = <<<EOD
        37.97.254.1
        2a01:7c8:3:1337::1
        192.168.200.1
        fd::1
        2a02:26f0:1180:35::210:6ad4
        EOD;

        $headers   = $this->getRawTestHeaders();
        $user      = new User(self::TEST_USER, self::TEST_HASH, UserAccess::READ_ONLY);
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
        $this->exitHandler->expects($this->once())
            ->method('echo')
            ->with($expectedOutput);

        $this->tableViewController->showList($headers);
    }
}
