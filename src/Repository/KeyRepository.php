<?php

namespace Portknock\Repository;

use Portknock\Helper\Log;

class KeyRepository extends AbstractFileRepository
{
    public const string FILE_KEY = "../../data/.key";

    public function getKey(): string
    {
        $key = $this->getOrCreateFile(self::FILE_KEY);

        if (!$key) {
            $key = bin2hex(random_bytes(32));
            $this->saveFile(self::FILE_KEY, $key);
            Log::info("AuthHashKey", "New key generated and saved to .key file");
        }

        return $key;
    }
}
