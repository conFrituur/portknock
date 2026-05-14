<?php

namespace Portknock\Controller;

use Portknock\Helper\Log;
use Portknock\Helper\OutputHandler;
use Portknock\Helper\Util;
use Portknock\Model\HttpHeaders;
use Portknock\Model\User;
use Portknock\Model\UserAccess;
use Portknock\Repository\AllowlistRepository;
use Portknock\Repository\ConfigRepository;
use Portknock\Repository\KeyRepository;
use Portknock\Repository\UserRepository;

abstract class AbstractController
{
    protected readonly AllowlistRepository $allowlistRepository;
    protected readonly UserRepository $userRepository;
    protected readonly KeyRepository $keyRepository;
    protected readonly ConfigRepository $configRepository;
    protected readonly OutputHandler $outputHandler;
    protected string $remoteAddr;

    public function __construct(
        protected readonly HttpHeaders $httpHeaders,
        ?AllowlistRepository $allowlistRepository = null,
        ?UserRepository $userRepository = null,
        ?KeyRepository $keyRepository = null,
        ?ConfigRepository $configRepository = null,
        ?OutputHandler $outputHandler = null
    ) {
        $this->allowlistRepository = $allowlistRepository ?? new AllowlistRepository();
        $this->userRepository      = $userRepository ?? new UserRepository();
        $this->keyRepository       = $keyRepository ?? new KeyRepository();
        $this->configRepository    = $configRepository ?? new ConfigRepository();
        $this->outputHandler       = $outputHandler ?? new OutputHandler();

        // even if not used in controller itself, it adds requesting ip context to the logs
        $this->parseAndValidateRemoteIpFromHeaders();
    }

    protected function getAndCheckAuthorizedUserFromHeaders(string $requestType, UserAccess $userAccess): User
    {
        $sesamCode = $this->httpHeaders->getSesam();

        if (!$sesamCode) {
            Log::notice("{$requestType} declined, no sesam header found");
            $this->outputHandler->die(401);
        }

        $authHash = Util::hash($sesamCode, $this->keyRepository->getKey());
        $user     = $this->userRepository->getUserByAuthHash($authHash);

        if (!$user) {
            // Do not log the whole access code, but just the beginning for debug purposes
            $truncatedSesam = substr($sesamCode, 0, 5) . '...';
            Log::notice("{$requestType} declined, no user found for sesam", ["truncated-header" => $truncatedSesam]);
            $this->outputHandler->die(401);
        }

        Log::addPersistentContext(['username' => $user->getName()]);

        if ($user->getUserAccess() !== $userAccess) {
            Log::notice("{$requestType} declined, {$user->getName()} does not have {$userAccess->value} permissions");
            $this->outputHandler->die(403);
        }

        return $user;
    }

    private function parseAndValidateRemoteIpFromHeaders(): void
    {
        $remoteAddr = $this->httpHeaders->getRemoteAddr();

        if (!$remoteAddr) {
            Log::error(HttpHeaders::HEADER_REMOTE_ADDR . " header is missing from request", ['headers' => $this->httpHeaders->getAll()]);
            $this->outputHandler->die(500);
        }

        Log::addPersistentContext(['ip' => $remoteAddr]);

        /** @var string $remoteAddr */
        if (!Util::isValidIPv4($remoteAddr) && !Util::isValidIPv6($remoteAddr)) {
            Log::error("invalid IP in header " . HttpHeaders::HEADER_REMOTE_ADDR);
            $this->outputHandler->die(500);
        }

        $this->remoteAddr = $remoteAddr;
    }
}
