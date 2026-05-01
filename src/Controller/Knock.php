<?php

namespace Portknock\Controller;

use Portknock\Helper\Log;
use Portknock\Helper\Util;
use Portknock\Model\AllowlistEntry;
use Portknock\Model\HttpHeaders;
use Portknock\Model\User;
use Portknock\Model\UserAccess;

class Knock extends AbstractController
{

    public function knock(array $headers): void
    {
        $headers        = new HttpHeaders($headers);
        $ip             = $this->getRemoteAddressFromHeaders($headers);
        $user           = $this->getAuthorizedUserFromHeaders($headers, $ip);
        $allowlistEntry = AllowlistEntry::create($user->getName(), $ip);
        $this->upsertEntryToAllowlist($allowlistEntry);
    }

    private function getAuthorizedUserFromHeaders(HttpHeaders $headers, string $remoteIp): User
    {
        $sesamCode = $headers->getSesam();

        if (!$sesamCode) {
            Log::warning($remoteIp, "Knock request declined, no sesam header found");
            $this->exitHandler->die(401);
        }

        $authHash = Util::hash($sesamCode, $this->keyRepository->getKey());
        $user     = $this->userRepository->getUserByAuthHash($authHash);

        if (!$user) {
            // Do not log the whole access code, but just the beginning for debug purposes
            $truncatedSesam = substr($sesamCode, 0, 5) . '...';
            Log::warning($remoteIp, "Knock request declined, unknown auth sesamHeader[={$truncatedSesam}]");
            $this->exitHandler->die(401);
        }

        if ($user->getUserAccess() !== UserAccess::WRITE_ONLY) {
            Log::warning($remoteIp, "Knock request declined, user {$user->getName()} does not have write permissions");
            $this->exitHandler->die(403);
        }

        Log::debug($remoteIp, "Knock request accepted for user {$user->getName()}");
        return $user;
    }

    private function getRemoteAddressFromHeaders(HttpHeaders $headers): string
    {
        $remoteIp = $headers->getRemoteAddr();

        if (!$remoteIp) {
            Log::error("MissingRemoteAddr", HttpHeaders::HEADER_REMOTE_ADDR . " header is missing from request");
            $this->exitHandler->die(500);
        }

        /** @var string $remoteIp */
        if (!Util::isValidIPv4($remoteIp) && !Util::isValidIPv6($remoteIp)) {
            Log::error($remoteIp, "invalid IP in header " . HttpHeaders::HEADER_REMOTE_ADDR . "[=$remoteIp]");
            $this->exitHandler->die(500);
        }

        return $remoteIp;
    }

    private function upsertEntryToAllowlist(AllowlistEntry $allowlistEntry): void
    {
        $allowlist = $this->allowlistRepository->getList();

        // Check if IPs are already allowlisted by this user, don't care for duplicates among other users at this point
        if ($allowlist->hasEntryInList($allowlistEntry)) {
            Log::debug($allowlistEntry->getUserName(), "skipping, {$allowlistEntry->getIpAddressesString()} is already whitelisted");
            return;
        }

        $allowlist = $allowlist->upsertEntry($allowlistEntry);

        $this->allowlistRepository->save($allowlist);
        Log::info($allowlistEntry->getUserName(), "{$allowlistEntry->getIpAddressesString()} has been added to the allowlist");
    }
}
