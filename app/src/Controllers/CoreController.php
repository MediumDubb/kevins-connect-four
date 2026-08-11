<?php

namespace MediumDubb\ConnectFour\Controllers;

use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Repositories\BoardRepo;
use MediumDubb\ConnectFour\Repositories\PlayerRepo;
use MediumDubb\ConnectFour\Repositories\TokenRepo;
use MediumDubb\ConnectFour\Services\ErrorService;
use MediumDubb\ConnectFour\Services\SessionService;

class CoreController
{
    private const string UID_KEY = 'UID';

    private SessionService $session;

    protected ErrorService $errorService;
    protected PDOConnector $db;

    public function __construct()
    {
        $this->errorService = ErrorService::get();
        $this->session = new SessionService();
        $this->db = new PDOConnector();
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

    public function getPlayerRepo(): PlayerRepo
    {
        return new PlayerRepo($this->db);
    }

    public function getBoardRepo(): BoardRepo
    {
        return new BoardRepo($this->db);
    }

    public function getTokenRepo(): TokenRepo
    {
        return new TokenRepo($this->db);
    }

    protected function getBaseURI(): string
    {
        return $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . "/";
    }

    protected function validatePlayerSession(string $room_id): bool
    {
        if ($session_uid = $this->getUid()) {
            $board_users = $this->getBoardRepo()->getRoomPlayerIDs($room_id);
            if (is_array($board_users)) {
                return in_array($session_uid, $board_users);
            }
        }

        $this->errorService->setError('You are not allowed to access this page');
        return false;
    }
}