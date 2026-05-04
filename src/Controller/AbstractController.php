<?php

namespace Portknock\Controller;

use Portknock\Helper\Log;
use Portknock\Helper\OutputHandler;
use Portknock\Helper\Util;
use Portknock\Model\HttpHeaders;
use Portknock\Repository\AllowlistRepository;
use Portknock\Repository\KeyRepository;
use Portknock\Repository\UserRepository;

abstract class AbstractController
{
    protected readonly AllowlistRepository $allowlistRepository;
    protected readonly UserRepository $userRepository;
    protected readonly KeyRepository $keyRepository;
    protected readonly OutputHandler $outputHandler;
    protected readonly HttpHeaders $httpHeaders;
    protected string $remoteAddr;

    public function __construct(
        array $headers,
        ?AllowlistRepository $allowlistRepository = null,
        ?UserRepository $userRepository = null,
        ?KeyRepository $keyRepository = null,
        ?OutputHandler $outputHandler = null
    ) {
        $this->allowlistRepository = $allowlistRepository ?? new AllowlistRepository();
        $this->userRepository      = $userRepository ?? new UserRepository();
        $this->keyRepository       = $keyRepository ?? new KeyRepository();
        $this->outputHandler       = $outputHandler ?? new OutputHandler();

        $this->httpHeaders = new HttpHeaders($headers);

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
