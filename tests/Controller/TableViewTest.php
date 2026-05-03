<?php

namespace Controller;

use Portknock\Controller\TableView;
use Portknock\Helper\OutputHandler;
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
    private OutputHandler $outputHandler;

    protected function setUp(): void
    {
        $this->allowlistRepository = $this->createMock(AllowlistRepository::class);
        $this->userRepository      = $this->createMock(UserRepository::class);
        $this->keyRepository       = $this->createMock(KeyRepository::class);
        $this->outputHandler       = $this->createMock(OutputHandler::class);
        $this->tableViewController = new TableView($this->allowlistRepository, $this->userRepository, $this->keyRepository, $this->outputHandler);

        parent::setUp();
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

        $this->tableViewController->showList($headers);
    }
    public function testMissingSesamHeader()
    {
        $headers = $this->getRawTestHeaders();
        unset($headers[HttpHeaders::HEADER_SESAM]);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(401)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->tableViewController->showList($headers);
    }

    public function testNoUserMatchForSesamHeader()
    {
        $headers                            = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_SESAM] = 'La-Di-Da-Di';

        $this->keyRepository->expects($this->once())
            ->method('getKey')
            ->willReturn(self::TEST_KEY);

        $this->userRepository->expects($this->once())
            ->method('getUserByAuthHash')
            ->willReturn(null);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(401)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->tableViewController->showList($headers);
    }

    public function testUserIncorrectPermissions()
    {
        $user    = new User(self::TEST_USER, UserAccess::WRITE_ONLY);
        $headers = $this->getRawTestHeaders();

        $this->keyRepository->expects($this->once())
            ->method('getKey')
            ->willReturn(self::TEST_KEY);

        $this->userRepository->expects($this->once())
            ->method('getUserByAuthHash')
            ->with(self::TEST_HASH)
            ->willReturn($user);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(403)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->tableViewController->showList($headers);
    }
}
