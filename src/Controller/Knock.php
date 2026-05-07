<?php

namespace Portknock\Controller;

use Portknock\Helper\Log;
use Portknock\Helper\Util;
use Portknock\Model\AllowlistEntry;
use Portknock\Model\User;
use Portknock\Model\UserAccess;

class Knock extends AbstractController
{
    public function knock(): void
    {
        $user           = $this->getAuthorizedUserFromHeaders();
        $allowlistEntry = AllowlistEntry::create($user->getName(), $this->remoteAddr);
        $this->upsertEntryToAllowlist($allowlistEntry);
    }

    private function getAuthorizedUserFromHeaders(): User
    {
        $sesamCode = $this->httpHeaders->getSesam();

        if (!$sesamCode) {
            Log::warning("knock request declined, no sesam header found");
            $this->outputHandler->die(401);
        }

        $authHash = Util::hash($sesamCode, $this->keyRepository->getKey());
        $user     = $this->userRepository->getUserByAuthHash($authHash);

        if (!$user) {
            // Do not log the whole access code, but just the beginning for debug purposes
            $truncatedSesam = substr($sesamCode, 0, 5) . '...';
            Log::warning("knock request declined, no user found for sesam", ["truncated-header" => $truncatedSesam]);
            $this->outputHandler->die(401);
        }

        Log::addPersistentContext(['username' => $user->getName()]);

        if ($user->getUserAccess() !== UserAccess::WRITE_ONLY) {
            Log::warning("knock request declined, user does not have read permissions");
            $this->outputHandler->die(403);
        }

        Log::debug("knock request accepted");
        return $user;
    }

    private function upsertEntryToAllowlist(AllowlistEntry $allowlistEntry): void
    {
        $allowlist = $this->allowlistRepository->getList();

        // Check if IPs are already allowlisted by this user, don't care for duplicates among other users at this point
        if ($allowlist->hasEntryInList($allowlistEntry)) {
            Log::debug("skipping, {$allowlistEntry->getIpAddressAndRangeString()} is already allowlisted");
            return;
        }

        $allowlist = $allowlist->upsertEntry($allowlistEntry);

        $this->allowlistRepository->save($allowlist);
        Log::info("{$allowlistEntry->getIpAddressAndRangeString()} has been added to the allowlist");
    }
}
