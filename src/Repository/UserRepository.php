<?php

namespace Portknock\Repository;

use Portknock\Model\User;

class UserRepository extends AbstractFileRepository
{
    public const string FILE_USERLIST = "../../data/users.json";

    public function getUserByAuthHash(string $authHash): ?User
    {
        $userlistJson = $this->getOrCreateFile(self::FILE_USERLIST);
        $userList     = json_decode($userlistJson, true, flags: JSON_THROW_ON_ERROR);

        if (array_key_exists($authHash, $userList)) {
            return User::fromJsonData($authHash, $userList[$authHash]);
        }

        return null;
    }
}
