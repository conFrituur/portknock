<?php

namespace Portknock\Controller;

use Portknock\Helper\ExitHandler;
use Portknock\Repository\AllowlistRepository;
use Portknock\Repository\KeyRepository;
use Portknock\Repository\UserRepository;

abstract class AbstractController
{
    protected AllowlistRepository $allowlistRepository;
    protected UserRepository $userRepository;
    protected KeyRepository $keyRepository;
    protected ExitHandler $exitHandler;

    public function __construct(
        ?AllowlistRepository $allowlistRepository = null,
        ?UserRepository $userRepository = null,
        ?KeyRepository $keyRepository = null,
        ?ExitHandler $exitHandler = null
    ) {
        $this->allowlistRepository = $allowlistRepository ?? new AllowlistRepository();
        $this->userRepository      = $userRepository ?? new UserRepository();
        $this->keyRepository       = $keyRepository ?? new KeyRepository();
        $this->exitHandler         = $exitHandler ?? new ExitHandler();
    }

}
