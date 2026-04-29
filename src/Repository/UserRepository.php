<?php

namespace Portknock\Repository;

use Exception;
use Portknock\Helper\Log;
use Portknock\Model\User;

class UserRepository extends AbstractFileRepository
{
    const string FILE_USERLIST = "../../data/users.json";

    public function getUserByAuthHash(string $authHash): ?User
    {
        $userlistJson = $this->getOrCreateFile(self::FILE_USERLIST);

        try {
            $userList = json_decode($userlistJson, true, flags: JSON_THROW_ON_ERROR);
        } catch (Exception) {
            // This will probably only occur on first run
            Log::notice("getUsers", "user.json was malformed or empty, starting anew");
            $userList = [];
        }

        if (array_key_exists($authHash, $userList)) {
            return User::fromArray($authHash, $userList[$authHash]);
        }

        return null;
    }

    public function getReadUserByAuthHash(string $authHash): ?User
    {
        $userlistJson = $this->getOrCreateFile(self::FILE_USERLIST);

        try {
            $userList = json_decode($userlistJson, true, flags: JSON_THROW_ON_ERROR);
        } catch (Exception) {
            // This will probably only occur on first run
            Log::notice("getUsers", "user.json was malformed or empty, starting anew");
            $userList = [];
        }

        if (array_key_exists($authHash, $userList)) {
            return new User($userList[$authHash], $authHash);
        }

        return null;
    }
}
