<?php

namespace Portknock;

use JsonException;
use Portknock\Util\KnockUtils;

class Knock
{
    private KnockUtils $utils;

    public function getUtils(): KnockUtils
    {
        return $this->utils;
    }

    public function __construct(array $headers)
    {
        $this->utils = new KnockUtils();

        $ip    = $this->getRemoteAddressFromHeaders($headers);
        $user  = $this->getAuthorizedUserFromHeaders($headers, $ip);
        $this->addIpToWhitelist($user, $ip);
    }

    private function getAuthorizedUserFromHeaders(array $headers, string $ip): string
    {
        $authorized = [
            'test' => 'test'
        ];

        $sesamHeader = $headers['HTTP_X_SESAM'] ?? 'UNSET';

        if (!array_key_exists($sesamHeader, $authorized)) {
            $truncatedSesam = strlen($sesamHeader) > 100 ? substr($sesamHeader, 0, 100) . '...' : $sesamHeader;
            $this->getUtils()->addLogEntry("{$ip} whitelist request declined, unauthorized user header '{$truncatedSesam}'");
            $this->getUtils()->die(401);
        }

        return $authorized[$sesamHeader];
    }

    private function getRemoteAddressFromHeaders(array $headers): string
    {
        $remoteIp = $headers['REMOTE_ADDR'] ?? 'not set';

        if (!$this->getUtils()->isValidateIPv4($remoteIp) && !$this->getUtils()->isValidateIPv6($remoteIp)) {
            $this->getUtils()->die(400);
        }

        return $remoteIp;
    }

    private function addIpToWhitelist(string $user, string $ip): void
    {
        $whitelistFile = $this->getUtils()->getOrCreateFile(KnockUtils::FILE_WHITELIST);
        try {
            $whitelist = json_decode($whitelistFile, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // This will probably only occur on first run
            $this->getUtils()->addLogEntry("whitelist.json was malformed or empty, starting anew");
            $whitelist = [];
        }

        // Check if IP is already whitelisted by this user, don't care for duplicates among other users at this point
        if (isset($whitelist[$user]) && $whitelist[$user] === $ip) {
            $this->getUtils()->addLogEntry("{$ip} is already whitelisted for {$user}");
            return;
        }

        $whitelist[$user] = $ip;

        file_put_contents(KnockUtils::FILE_WHITELIST, json_encode($whitelist));
        $this->getUtils()->addLogEntry("{$ip} has been added to the whitelist for {$user}");
    }


}

(new Knock($_SERVER));
