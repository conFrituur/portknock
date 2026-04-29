<?php

namespace Portknock\Model;

readonly class User
{
    public const string FIELD_NAME = 'name';
    public const string FIELD_ACCESS = 'access';

    public function __construct(private string $authorizationHash, private string $name, private UserAccess $userAccess)
    {
    }

    public function getAuthorizationHash(): string
    {
        return $this->authorizationHash;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUserAccess(): UserAccess
    {
        return $this->userAccess;
    }

    public static function fromArray(string $authorizationHash, array $fields): self
    {
        return new self($authorizationHash, $fields[self::FIELD_NAME], $fields[self::FIELD_ACCESS]);
    }
}
