<?php

namespace Portknock\Model;

use Portknock\Helper\Util;
use RuntimeException;

readonly class User
{
    public const string FIELD_NAME = 'name';
    public const string FIELD_ACCESS = 'access';

    public function __construct(private string $name, private string $authorizationHash, private UserAccess $userAccess)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAuthorizationHash(): string
    {
        return $this->authorizationHash;
    }

    public function getUserAccess(): UserAccess
    {
        return $this->userAccess;
    }

    public static function fromJsonData(string $authorizationHash, array $fields): self
    {
        $keys = array_keys($fields);
        if (array_diff($keys, [self::FIELD_NAME, self::FIELD_ACCESS]) !== []) {
            throw new RuntimeException("Not any or all field keys are found in given JsonData, given keys: " . json_encode($keys));
        }

        $usersAccess = UserAccess::tryFrom($fields[self::FIELD_ACCESS]);
        if (!$usersAccess) {
            throw new RuntimeException("Invalid UserAccess[={$fields[self::FIELD_ACCESS]}] for user[={$fields[self::FIELD_NAME]}]");
        }
        return new self($fields[self::FIELD_NAME], $authorizationHash, $usersAccess);
    }
}
