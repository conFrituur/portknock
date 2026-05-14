<?php

namespace Portknock\Controller;

use Portknock\Helper\Log;
use Portknock\Helper\Util;
use Portknock\Model\Allowlist;
use Portknock\Model\AllowlistEntry;
use Portknock\Model\User;
use Portknock\Model\UserAccess;

class Knock extends AbstractController
{
    private Allowlist $allowlist;

    public function knock(): void
    {
        $amendKeyHash      = $this->getAmendKeyHashFromHeaders();
        $user              = $this->getAndCheckAuthorizedUserFromHeaders('knock request', UserAccess::WRITE_ONLY);
        $this->allowlist   = $this->allowlistRepository->getList();
        $newAllowlistEntry = AllowlistEntry::createFromAddress($user->getName(), $this->remoteAddr);

        if (!$amendKeyHash) {
            Log::debug("processing first-knock request from {$user->getName()}");
            $this->firstKnock($newAllowlistEntry);
        } else {
            Log::debug("processing second-knock request from {$user->getName()}");
            $this->secondKnock($amendKeyHash, $user, $newAllowlistEntry);
        }
    }

    private function getAmendKeyHashFromHeaders(): ?string
    {
        $amendKey = $this->httpHeaders->getAmendKeyFromQuery();

        if ($amendKey === null) {
            return null;
        }

        $amendKeyHash = Util::hash($amendKey, $this->keyRepository->getKey());
        Log::addPersistentContext(['amendKeyHash' => $amendKeyHash]);
        return $amendKeyHash;
    }



    private function firstKnock(AllowlistEntry $newAllowlistEntry): void
    {
        if ($this->allowlist->hasEntryInList($newAllowlistEntry)) {
            Log::debug("skipping, {$newAllowlistEntry->getIpAddressAndRangeString()} is already on allowlist");
            $this->outputHandler->die(200, "Already on allowlist");
        }

        // Only redirect when not already redirected && shouldRedirect
        $shouldRedirectForSecondKnock = $this->shouldRedirectForSecondKnock($newAllowlistEntry);

        if ($shouldRedirectForSecondKnock) {
            $newAmendKey       = $this->keyRepository->generateRandomKey();
            $newAmendKeyHash   = Util::hash($newAmendKey, $this->keyRepository->getKey());
            $newAllowlistEntry = $newAllowlistEntry->addAmendKeyHash($newAmendKeyHash);
        }

        $this->upsertEntryToAllowlist($newAllowlistEntry);
        Log::info("first-knock successful, {$newAllowlistEntry->getIpAddressAndRangeString()} has been written to allowlist");

        if ($shouldRedirectForSecondKnock) {
            /** @var string $redirectUrl */
            $redirectUrl = $this->getRedirectHostUrl($newAllowlistEntry, $newAmendKey);
            Log::debug(
                "redirected for second-knock to retrieve {$newAllowlistEntry->getMissingDataIpVersion()}",
                [
                    "redirect-host"  => parse_url($redirectUrl, PHP_URL_HOST),
                    'amend-key-hash' => $newAmendKeyHash,
                ]
            );
            $this->outputHandler->redirect($redirectUrl);
        }

        $this->outputHandler->echo("200 Added to allowlist");
    }

    private function secondKnock(string $amendKeyHash, User $user, AllowlistEntry $newAllowlistEntry): void
    {
        $mergedAllowlistEntry = $this->amendAllowlistEntry($newAllowlistEntry, $user->getName(), $amendKeyHash);

        $this->upsertEntryToAllowlist($mergedAllowlistEntry);
        Log::info("second-knock successful, {$newAllowlistEntry->getIpAddressAndRangeString()} has been amended to {$user->getName()}'s allowlist");
        $this->outputHandler->echo("200 Added to allowlist++");
    }

    private function upsertEntryToAllowlist(AllowlistEntry $newAllowlistEntry): void
    {
        $this->allowlist = $this->allowlist->upsertEntry($newAllowlistEntry);
        $this->allowlistRepository->save($this->allowlist);
    }

    private function amendAllowlistEntry(AllowlistEntry $newAllowlistEntry, string $userName, string $amendKeyHash): AllowlistEntry
    {
        $previousAllowlistEntry = $this->allowlist->getAllowlistEntryByUserNameAmendKey($userName, $amendKeyHash);

        if (!$previousAllowlistEntry) {
            Log::notice('second-knock rejected, no entry found for provided user && amendKey');
            $this->outputHandler->die(403);
        }

        if ($previousAllowlistEntry->getMissingDataIpVersion() === null) {
            Log::notice('second-knock rejected, previous AllowlistEntry has no missing IP information', ['previous-entry' => $previousAllowlistEntry]);
            $this->outputHandler->die(409, "Nothing to amend");
        }

        if ($previousAllowlistEntry->getMissingDataIpVersion() === $newAllowlistEntry->getMissingDataIpVersion()) {
            Log::notice(
                "second-knock failed, remote address is not {$newAllowlistEntry->getMissingDataIpVersion()}",
                ['previous-entry' => $previousAllowlistEntry]
            );
            $this->outputHandler->die(409, "Request from same IP version, expected {$previousAllowlistEntry->getMissingDataIpVersion()}");
        }

        // First knock takes precedence
        $ipv4Address = $previousAllowlistEntry->getIpv4Address() ?? $newAllowlistEntry->getIpv4Address();
        $ipv6Range   = $previousAllowlistEntry->getIpv6Range() ?? $newAllowlistEntry->getIpv6Range();

        return new AllowlistEntry($previousAllowlistEntry->getUserName(), $ipv4Address, $ipv6Range);
    }

    private function shouldRedirectForSecondKnock(AllowlistEntry $newAllowlistEntry): bool
    {
        return
            $this->httpHeaders->getAmendKeyFromQuery() === null
            && $this->getRedirectHostUrl($newAllowlistEntry) !== null;
    }

    private function getRedirectHostUrl(AllowlistEntry $newAllowlistEntry, ?string $amendKey = null): ?string
    {
        $ipVersionMissing = $newAllowlistEntry->getMissingDataIpVersion();
        $config           = $this->configRepository->getConfig();
        $redirectHost     = null;

        switch ($ipVersionMissing) {
            case AllowlistEntry::FIELD_IPV4:
                $redirectHost = $config->getV4RedirectHost();
                break;
            case AllowlistEntry::FIELD_IPV6:
                $redirectHost = $config->getV6RedirectHost();
                break;
        }

        if (!$redirectHost) {
            return null;
        }

        $redirectHostUrl = "https://$redirectHost{$this->httpHeaders->getRequestUriPath()}";

        if ($amendKey) {
            $redirectHostUrl .= "?amend={$amendKey}";
        }

        return $redirectHostUrl;
    }
}
