<?php

namespace Portknock;

use Portknock\Helper\Log;
use Portknock\Helper\Util;
use Portknock\Model\HttpHeaders;
use Portknock\Model\UserAccess;
use Portknock\Repository\AllowlistRepository;
use Portknock\Repository\KeyRepository;
use Portknock\Repository\UserRepository;

class TableView
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

    public function showList(array $headers): void
    {
        $headers = new HttpHeaders($headers);
        $this->checkAuthorizedUserFromHeaders($headers);
        $allowedIps = $this->getAllowedIps();
        echo $this->buildOPNsenseTable($allowedIps);
    }

    private function checkAuthorizedUserFromHeaders(HttpHeaders $headers): void
    {
        $remoteIp  = $headers->getRemoteAddr() ?? 'Unknown';
        $sesamCode = $headers->getSesamHeader();

        if (!$sesamCode) {
            Log::warning($remoteIp, "View request declined, no sesam header found");
            Util::die(401);
        }

        $authHash = hash_hmac('sha256', $sesamCode, $this->keyRepository->getKey());
        $user     = $this->userRepository->getUserByAuthHash($authHash);

        if (!$user) {
            // Do not log the whole access code, but just the beginning for debug purposes
            $truncatedSesam = substr($sesamCode, 0, 5) . '...';
            Log::warning($remoteIp, "View request declined, unknown auth sesamHeader '$truncatedSesam'");
            Util::die(401);
        }

        if ($user->getUserAccess() !== UserAccess::READ_ONLY) {
            Log::warning($remoteIp, "View request declined, user {$user->getName()} does not have read permissions");
            Util::die(403);
        }

        Log::debug($remoteIp, "View request accepted for user {$user->getName()}");
    }

    private function getAllowedIps(): array
    {
        $allowlist = $this->allowlistRepository->getList();

        // Remove any duplicate IP's from list
        return array_unique($allowlist->toArrayOfIps());
    }

    /**
     * OPNsense table format:
     * "The content of the file being fetched should contain one IPv[4|6] address per line,
     * lines that start with a whitespace , colon (,), semicolon (;), pipe (|) or hash (#) will be ignored."
     *
     * @param array $allowlistData
     * @return string
     */
    private function buildOPNsenseTable(array $allowlistData): string
    {
        return implode(PHP_EOL, $allowlistData);
    }
}
