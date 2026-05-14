<?php

namespace Portknock\Controller;

use Portknock\Helper\Log;
use Portknock\Helper\Util;
use Portknock\Model\UserAccess;

class CheckView extends AbstractController
{
    public function isIpOnAllowlist(): void
    {
        $user = $this->getAndCheckAuthorizedUserFromHeaders('check-ip', UserAccess::READ_ONLY);
        $ipToCheck = $this->getIpToCheckFromPostParameters();
        Log::debug("check-ip request from '{$user->getName()}' for '{$ipToCheck}'");

        $allowlist = $this->allowlistRepository->getList();
        $isAllowlisted = $allowlist->hasIpAddressInList($ipToCheck);

        $response = [
            'isAllowlisted' => $isAllowlisted,
        ];

        $this->outputHandler->echo(json_encode($response, JSON_THROW_ON_ERROR));
    }

    private function getIpToCheckFromPostParameters(): string
    {
        $ip = $_POST['ip'] ?? '';
        $ip = strval($ip);

        if (!Util::isValidIPv4($ip) && !Util::isValidIPv6($ip)) {
            Log::error("No valid IP in post parameter found", ['posted-ip' => $ip]);
            $this->outputHandler->die(400);
        }

        return $ip;
    }

}
