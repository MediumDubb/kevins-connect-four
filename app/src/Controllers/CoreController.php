<?php

namespace MediumDubb\ConnectFour\Controllers;

use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Repositories\BoardRepo;
use MediumDubb\ConnectFour\Repositories\PlayerRepo;
use MediumDubb\ConnectFour\Repositories\TokenRepo;
use MediumDubb\ConnectFour\Services\SessionService;

class CoreController
{
    private const string UID_KEY = 'UID';

    private SessionService $session;

    protected PDOConnector $db;

    public function __construct()
    {
        $this->session = new SessionService();
        $this->db = new PDOConnector();
    }

    public function getUid(): ?string
    {
        $this->session->clear();
        if (!$uid = $this->session->get(self::UID_KEY)) {
            $uid = $_GET['playerID'] ?? $this->getPlayerRepo()->getNewPlayerID();
            $this->session->set(self::UID_KEY, $uid);
        }

        return $uid;
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

        return false;
    }
}