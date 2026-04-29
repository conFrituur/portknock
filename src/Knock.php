<?php

namespace Portknock;

use Portknock\Helper\Log;
use Portknock\Helper\Util;
use Portknock\Model\AllowlistEntry;
use Portknock\Model\HttpHeaders;
use Portknock\Model\User;
use Portknock\Model\UserAccess;
use Portknock\Repository\AllowlistRepository;
use Portknock\Repository\KeyRepository;
use Portknock\Repository\UserRepository;

class Knock
{
    private AllowlistRepository $allowlistRepository;
    private UserRepository $userRepository;
    private KeyRepository $keyRepository;

    public function __construct(
        ?AllowlistRepository $allowlistRepository = null,
        ?UserRepository $userRepository = null,
        ?KeyRepository $keyRepository = null
    ) {
        $this->allowlistRepository = $allowlistRepository ?? new AllowlistRepository();
        $this->userRepository      = $userRepository ?? new UserRepository();
        $this->keyRepository       = $keyRepository ?? new KeyRepository();
    }

    public function knock(array $headers): void
    {
        $headers = new HttpHeaders($headers);
        $ip = $this->getRemoteAddressFromHeaders($headers);
        $user = $this->getAuthorizedUserFromHeaders($headers, $ip);
        $allowlistEntry = AllowlistEntry::create($user, $ip);
        $this->upsertEntry($allowlistEntry);
    }

    private function getAuthorizedUserFromHeaders(HttpHeaders $headers, string $remoteIp): User
    {
        $sesamCode = $headers->getSesamHeader();

        if (!$sesamCode) {
            Log::warning($remoteIp, "Knock request declined, no sesam header found");
            Util::die(401);
        }

        $authHash = hash_hmac('sha256', $sesamCode, $this->keyRepository->getKey());
        $user = $this->userRepository->getUserByAuthHash($authHash);

        if (!$user) {
            // Do not log the whole access code, but just the beginning for debug purposes
            $truncatedSesam = substr($sesamCode, 0, 5) . '...';
            Log::warning($remoteIp, "Knock request declined, unknown auth sesamHeader[={$truncatedSesam}]");
            Util::die(401);
        }

        if ($user->getUserAccess() !== UserAccess::WRITE_ONLY) {
            Log::warning($remoteIp, "Knock request declined, user {$user->getName()} does not have write permissions");
            Util::die(403);
        }

        Log::debug($remoteIp, "Knock request accepted for user {$user->getName()}");
        return $user;
    }

    private function getRemoteAddressFromHeaders(HttpHeaders $headers): string
    {
        $remoteIp = $headers->getRemoteAddr();

        if (!$remoteIp) {
            Log::error("MissingRemoteAddr", HttpHeaders::REMOTEADDR_HEADER . " header is missing from request");
        }

        /** @var string $remoteIp */
        if (!Util::isValidIPv4($remoteIp) && !Util::isValidIPv6($remoteIp)) {
            Log::error($remoteIp, "Could not find valid IP in header " . HttpHeaders::REMOTEADDR_HEADER . "[={$remoteIp}]");
            Util::die(400);
        }

        return $remoteIp;
    }

    private function upsertEntry(AllowlistEntry $allowlistEntry): void
    {
        $allowlist = $this->allowlistRepository->getList();

        // Check if IPs are already allowlisted by this user, don't care for duplicates among other users at this point
        if ($allowlist->hasEntryInList($allowlistEntry)) {
            Log::debug(
                $allowlistEntry->getUserName(),
                "skipping, {$allowlistEntry->getIpAddressesString()} is already whitelisted"
            );
            return;
        }

        $allowlist->upsertEntry($allowlistEntry);

        $this->allowlistRepository->save($allowlist);
        Log::info(
            $allowlistEntry->getUserName(),
            "{$allowlistEntry->getIpAddressesString()} has been added to the allowlist"
        );
    }
}
