<?php

namespace Portknock\Repository;

use Exception;
use Portknock\Helper\Log;
use Portknock\Model\Allowlist;

class AllowlistRepository extends AbstractFileRepository
{
    public const string FILE_ALLOWLIST = "../data/allowlist.json";

    public function getList(): Allowlist
    {
        $allowlistEncoded = $this->getOrCreateFile(self::FILE_ALLOWLIST);

        try {
            $allowlist = Allowlist::fromJson($allowlistEncoded);
        } catch (Exception) {
            // This will probably only occur on first run
            Log::notice("data/allowlist.json was malformed or empty, starting anew");
            $allowlist = new Allowlist([]);
        }

        return $allowlist;
    }

    public function save(Allowlist $allowlist): void
    {
        $this->saveFile(self::FILE_ALLOWLIST, $allowlist->toJson());
    }
}
