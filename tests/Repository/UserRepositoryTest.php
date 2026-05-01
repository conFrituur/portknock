<?php

namespace Portknock\Tests\Repository;

use Portknock\Helper\FileHandler;
use Portknock\Model\User;
use Portknock\Model\UserAccess;
use Portknock\Repository\UserRepository;
use Portknock\Tests\AbstractCase;

class UserRepositoryTest extends AbstractCase
{
    public function testGetUserByAuthHash(): void
    {
        $expectedUser = new User(self::TEST_USER_2, self::TEST_HASH_2, UserAccess::READ_ONLY);

        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(UserRepository::FILE_USERLIST)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(UserRepository::FILE_USERLIST)
            ->willReturn($this->getTestUserListJson());

        $userRepository = new UserRepository($mockFileHandler);
        $actualUser     = $userRepository->getUserByAuthHash(self::TEST_HASH_2);
        self::assertEquals($expectedUser, $actualUser);
    }

    public function testGetUserByAuthHashUnknown(): void
    {
        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(UserRepository::FILE_USERLIST)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(UserRepository::FILE_USERLIST)
            ->willReturn($this->getTestUserListJson());

        $userRepository = new UserRepository($mockFileHandler);
        $actualUser     = $userRepository->getUserByAuthHash('Dunno');
        self::assertNull($actualUser);
    }

    protected function getTestUserListJson(): string
    {
        return json_encode([
            self::TEST_HASH   => [User::FIELD_NAME => self::TEST_USER, User::FIELD_ACCESS => UserAccess::READ_ONLY],
            self::TEST_HASH_2 => [User::FIELD_NAME => self::TEST_USER_2, User::FIELD_ACCESS => UserAccess::READ_ONLY],
            self::TEST_HASH_3 => [User::FIELD_NAME => self::TEST_USER_3, User::FIELD_ACCESS => UserAccess::WRITE_ONLY],
        ]);
    }
}
