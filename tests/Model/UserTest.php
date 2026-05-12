<?php

namespace Portknock\Tests\Model;

use Portknock\Model\User;
use Portknock\Model\UserAccess;
use Portknock\Tests\AbstractCase;
use RuntimeException;

class UserTest extends AbstractCase
{
    public function testFromJsonData(): void
    {
        $expectedUser = new User(self::TEST_USER, UserAccess::READ_ONLY);
        $actualUser   = User::fromJsonData(
            [User::FIELD_NAME => self::TEST_USER, User::FIELD_ACCESS => UserAccess::READ_ONLY->value]
        );

        self::assertEquals($expectedUser, $actualUser);
        self::assertSame(self::TEST_USER, $actualUser->getName());
        self::assertSame(UserAccess::READ_ONLY, $actualUser->getUserAccess());
    }

    public function testErrorFromInvalidJsonData(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not any or all field keys are found in given JsonData, given keys: ["herp"]');
        User::fromJsonData(
            ['herp' => 'derp']
        );
    }

    public function testErrorFromJsonDataInvalidAccess(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid UserAccess[=Dunno] for user[=Test]');
        User::fromJsonData(
            [User::FIELD_NAME => self::TEST_USER, User::FIELD_ACCESS => 'Dunno']
        );
    }
}
