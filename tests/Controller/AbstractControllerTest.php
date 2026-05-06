<?php

namespace Portknock\Tests\Controller;

use Portknock\Model\User;
use Portknock\Tests\Mock\AbstractControllerMock;
use Portknock\Helper\OutputHandler;
use Portknock\Model\HttpHeaders;
use Portknock\Repository\AllowlistRepository;
use Portknock\Repository\KeyRepository;
use Portknock\Repository\UserRepository;
use Portknock\Tests\AbstractCase;
use Portknock\Tests\Mock\MockException;

class AbstractControllerTest extends AbstractCase
{
    protected HttpHeaders $headers;
    protected AllowlistRepository $allowlistRepository;
    protected UserRepository $userRepository;
    protected KeyRepository $keyRepository;
    protected OutputHandler $outputHandler;

    protected function setUp(): void
    {
        $this->headers             = $this->getTestHeaders();
        $this->allowlistRepository = $this->createMock(AllowlistRepository::class);
        $this->userRepository      = $this->createMock(UserRepository::class);
        $this->keyRepository       = $this->createMock(KeyRepository::class);
        $this->outputHandler       = $this->createMock(OutputHandler::class);
        parent::setUp();
    }

    public function testMissingRemoteAddr()
    {
        $headers = $this->getRawTestHeaders();
        unset($headers[HttpHeaders::HEADER_REMOTE_ADDR]);
        $this->headers = new HttpHeaders($headers);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(500)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->constructAbstractControllerMock();
    }

    public function testInvalidRemoteAddr()
    {
        $headers                                  = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_REMOTE_ADDR] = 'La-Di-Da-Di';
        $this->headers = new HttpHeaders($headers);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(500)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
        $this->constructAbstractControllerMock();
    }

    protected function prepMissingSesamHeader(): void
    {
        $headers = $this->getRawTestHeaders();
        unset($headers[HttpHeaders::HEADER_SESAM]);
        $this->headers = new HttpHeaders($headers);

        $this->outputHandler->expects($this->once())
            ->method('die')
            ->with(401)
            ->willThrowException(new MockException());

        $this->expectException(MockException::class);
    }

    protected function prepNoUserMatchForSesamHeader(): void
    {
        $headers                            = $this->getRawTestHeaders();
        $headers[HttpHeaders::HEADER_SESAM] = 'La-Di-Da-Di';
        $this->headers = new HttpHeaders($headers);

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
    }

    protected function prepUserIncorrectPermissions(User $user): void
    {
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
    }

    private function constructAbstractControllerMock(): void
    {
        new AbstractControllerMock($this->headers, $this->allowlistRepository, $this->userRepository, $this->keyRepository, $this->outputHandler);
    }
}
