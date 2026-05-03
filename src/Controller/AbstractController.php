<?php

namespace Portknock\Controller;

use Portknock\Helper\OutputHandler;
use Portknock\Repository\AllowlistRepository;
use Portknock\Repository\KeyRepository;
use Portknock\Repository\UserRepository;

abstract class AbstractController
{
    protected AllowlistRepository $allowlistRepository;
    protected UserRepository $userRepository;
    protected KeyRepository $keyRepository;
    protected OutputHandler $outputHandler;

    public function __construct(
        ?AllowlistRepository $allowlistRepository = null,
        ?UserRepository $userRepository = null,
        ?KeyRepository $keyRepository = null,
        ?OutputHandler $outputHandler = null
    ) {
        $this->allowlistRepository = $allowlistRepository ?? new AllowlistRepository();
        $this->userRepository      = $userRepository ?? new UserRepository();
        $this->keyRepository       = $keyRepository ?? new KeyRepository();
        $this->outputHandler       = $outputHandler ?? new OutputHandler();
    }

}
