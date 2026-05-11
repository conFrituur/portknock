<?php

namespace Portknock\Controller;

use Portknock\Helper\Log;
use Portknock\Helper\OutputHandler;
use Portknock\Helper\Util;
use Portknock\Model\HttpHeaders;
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
