<?php

namespace Portknock\Tests\Controller;

use Portknock\Controller\Knock;
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

class KnockTest extends AbstractCase
{
    private Knock $knockController;
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
        $this->knockController     = new Knock($this->allowlistRepository, $this->userRepository, $this->keyRepository, $this->exitHandler);
    }

    public function testSuccessfulKnock()
    {
        $headers = $this->getRawTestHeaders();
        $user              = new User(self::TEST_USER, self::TEST_HASH, UserAccess::WRITE_ONLY);
        $emptyAllowList    = new Allowlist([]);
        $expectedAllowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, null, self::REMOTE_ADDR),
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

        $this->knockController->knock($headers);
    }

    public function testSuccessfulKnockAlreadyAllowlisted()
    {
        $headers = $this->getRawTestHeaders();
        $user      = new User(self::TEST_USER, self::TEST_HASH, UserAccess::WRITE_ONLY);
        $allowList = new Allowlist([
            new AllowlistEntry(self::TEST_USER, null, self::REMOTE_ADDR),
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

        $this->knockController->knock($headers);
    }

    public function testMissingRemoteAddr()
    {
        $headers = $this->getRawTestHeaders();
        unset($headers[HttpHeaders::HEADER_REMOTE_ADDR]);

        $this->exitHandler->expects($this->once())
            ->method('die')
            ->with(500)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->knockController->knock($headers);
    }

    public function testInvalidRemoteAddr()
    {
        $headers                                  = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_REMOTE_ADDR] = 'La-Di-Da-Di';

        $this->exitHandler->expects($this->once())
            ->method('die')
            ->with(500)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->knockController->knock($headers);
    }

    public function testMissingSesamHeader()
    {
        $headers = $this->getRawTestHeaders();
        unset($headers[HttpHeaders::HEADER_SESAM]);

        $this->exitHandler->expects($this->once())
            ->method('die')
            ->with(401)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->knockController->knock($headers);
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

        $this->exitHandler->expects($this->once())
            ->method('die')
            ->with(401)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->knockController->knock($headers);
    }

    public function testUserIncorrectPermissions()
    {
        $user    = new User(self::TEST_USER, self::TEST_HASH, UserAccess::READ_ONLY);
        $headers = $this->getRawTestHeaders();

        $this->keyRepository->expects($this->once())
            ->method('getKey')
            ->willReturn(self::TEST_KEY);

        $this->userRepository->expects($this->once())
            ->method('getUserByAuthHash')
            ->with(self::TEST_HASH)
            ->willReturn($user);

        $this->exitHandler->expects($this->once())
            ->method('die')
            ->with(403)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->knockController->knock($headers);
    }

}
