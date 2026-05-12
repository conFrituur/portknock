<?php

namespace Portknock\Controller;

use Portknock\Helper\Log;
use Portknock\Helper\Util;
use Portknock\Model\UserAccess;

class TableView extends AbstractController
{
    public function showList(): void
    {
        $this->checkAuthorizedUserFromHeaders();
        $allowedIps = $this->getAllowedIps();
        $this->outputHandler->echo($this->buildOPNsenseTable($allowedIps));
    }

    private function checkAuthorizedUserFromHeaders(): void
    {
        $sesamCode = $this->httpHeaders->getSesam();

        if (!$sesamCode) {
            Log::warning("view request declined, no sesam header found");
            $this->outputHandler->die(401);
        }

        $authHash = Util::hash($sesamCode, $this->keyRepository->getKey());
        $user     = $this->userRepository->getUserByAuthHash($authHash);

        if (!$user) {
            // Do not log the whole access code, but just the beginning for debug purposes
            $truncatedSesam = substr($sesamCode, 0, 5) . '...';
            Log::warning("view request declined, no user found for sesam", ["truncated-header" => $truncatedSesam]);
            $this->outputHandler->die(401);
        }

        Log::addPersistentContext(['username' => $user->getName()]);

        if ($user->getUserAccess() !== UserAccess::READ_ONLY) {
            Log::warning("view request declined, user does not have read permissions");
            $this->outputHandler->die(403);
        }

        Log::debug("view request accepted for {$user->getName()}");
    }

    private function getAllowedIps(): array
    {
        $allowlist = $this->allowlistRepository->getList();

        // Remove any duplicate IP's from list
        return array_unique($allowlist->toArrayOfIpsAndRanges());
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
