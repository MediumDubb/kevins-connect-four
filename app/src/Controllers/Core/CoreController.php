<?php

namespace MediumDubb\ConnectFour\Controllers\Core;

use MediumDubb\ConnectFour\Core\ApiError;
use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Repositories\BoardRepo;
use MediumDubb\ConnectFour\Repositories\PlayerRepo;
use MediumDubb\ConnectFour\Repositories\TokenRepo;
use MediumDubb\ConnectFour\Services\SessionService;

class CoreController
{
    public ApiError $errors;

    private const string UID_KEY = 'UID';

    private static TokenRepo $tokenRepo;
    private static BoardRepo $boardRepo;
    private static PlayerRepo $playerRepo;

    public function __construct(private readonly SessionService $session, public readonly PDOConnector $db){
        $this->errors = ApiError::get();
        self::$tokenRepo = new TokenRepo($this->db);
        self::$boardRepo = new BoardRepo($this->db);
        self::$playerRepo = new PlayerRepo($this->db);
    }

    public static function getTokenRepository(): TokenRepo
    {
        return self::$tokenRepo;
    }

    public static function getBoardRepository(): BoardRepo
    {
        return self::$boardRepo;
    }

    public static function getPlayerRepository(): PlayerRepo
    {
        return self::$playerRepo;
    }

    public function getUid(): ?string
    {
        return $this->session->get(self::UID_KEY);
    }

    public function setUid(string $id): SessionService
    {
        return $this->session->set(self::UID_KEY, $id);
    }

    public function clearUid(): void
    {
        $this->session->clear();
    }
}