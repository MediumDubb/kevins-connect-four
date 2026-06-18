<?php

namespace MediumDubb\ConnectFour\Services;

use MediumDubb\ConnectFour\Repositories\BoardRepo;
use MediumDubb\ConnectFour\Repositories\PlayerRepo;
use MediumDubb\ConnectFour\Repositories\TokenRepo;

class GameService
{
    /**
     * Handle Game Logic for Controller
     */

    public function __construct(
        private readonly SessionService $sessionService,
        private BoardRepo $boardRepo,
        private PlayerRepo $playerRepo,
        private TokenRepo $tokenRepo
    ) {}

    public function assignPlayer()
    {
        $this->sessionService->set('');
    }
}