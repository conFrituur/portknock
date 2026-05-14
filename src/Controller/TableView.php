<?php

namespace Portknock\Controller;

use Portknock\Helper\Log;
use Portknock\Model\UserAccess;

class TableView extends AbstractController
{
    public function showList(): void
    {
        $user = $this->getAndCheckAuthorizedUserFromHeaders('view', UserAccess::READ_ONLY);
        Log::debug("view request accepted for {$user->getName()}");
        $allowedIps = $this->getAllowedIps();
        $this->outputHandler->echo($this->buildOPNsenseTable($allowedIps));
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
