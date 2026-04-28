<?php

namespace Portknock;

use JsonException;
use Portknock\Util\KnockUtils;

class Knock
{
    private KnockUtils $utils;

    public function __construct(?KnockUtils $utils = null)
    {
        $this->utils = $utils ?? new KnockUtils();
    }

    public function knock(array $headers): void
    {
        $ip   = $this->getRemoteAddressFromHeaders($headers);
        $user = $this->getAuthorizedUserFromHeaders($headers, $ip);
        $this->addIpToWhitelist($user, $ip);
    }

    private function getAuthorizedUserFromHeaders(array $headers, string $ip): string
    {
        $authorized = [
            'SesamOpenU' => 'Test',
        ];

        $sesamHeader = $headers['HTTP_X_SESAM'] ?? 'UNSET';

        if (!array_key_exists($sesamHeader, $authorized)) {
            $truncatedSesam = strlen($sesamHeader) > 100 ? substr($sesamHeader, 0, 100) . '...' : $sesamHeader;
            $this->utils->addLogEntry("{$ip} whitelist request declined, unauthorized user header '{$truncatedSesam}'");
            $this->utils->die(401);
        }

        return $authorized[$sesamHeader];
    }

    private function getRemoteAddressFromHeaders(array $headers): string
    {
        $remoteIp = $headers['REMOTE_ADDR'] ?? 'not set';

        if (!$this->utils->isValidIPv4($remoteIp) && !$this->utils->isValidIPv6($remoteIp)) {
            $this->utils->die(400);
        }

        return $remoteIp;
    }

    private function addIpToWhitelist(string $user, string $ip): void
    {
        $whitelistFile = $this->utils->getOrCreateFile(KnockUtils::FILE_WHITELIST);
        try {
            $whitelist = json_decode($whitelistFile, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // This will probably only occur on first run
            $this->utils->addLogEntry("whitelist.json was malformed or empty, starting anew");
            $whitelist = [];
        }

        // Check if IP is already whitelisted by this user, don't care for duplicates among other users at this point
        if (isset($whitelist[$user]) && $whitelist[$user] === $ip) {
            $this->utils->addLogEntry("{$ip} is already whitelisted for {$user}");
            return;
        }

        $whitelist[$user] = $ip;

        $this->utils->save(KnockUtils::FILE_WHITELIST, strval(json_encode($whitelist)));
        $this->utils->addLogEntry("{$ip} has been added to the whitelist for {$user}");
    }
}
