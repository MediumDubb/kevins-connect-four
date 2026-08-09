<?php

namespace MediumDubb\ConnectFour\Controllers;

use JetBrains\PhpStorm\NoReturn;

class EntryController extends CoreController
{
    private static array $user_actions = [
        'join',
        'create'
    ];

    public function index(): void
    {
        require_once (dirname(__DIR__) . "/Views/Entry.php");
    }

    public function gameRoom(string $id): void
    {
        $valid_player = $this->validatePlayerSession($id);
        if ($valid_player) {
            require_once (dirname(__DIR__) . "/Views/Room.php");
        } else {
            header("location: {$this->getBaseURI()}");
        }
    }

    #[NoReturn]
    public function initGame(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->clearUid(); //test
            $action = $this->getValidatedAction();
            $player_id = $this->getPlayerID();
            $room_id = $this->getRoomID($player_id, $action);
            if ($room_id) {
                $redirect_url = $this->getRoomRedirect($room_id);
                header("Location: $redirect_url");
            } else {
                header("Location: /");
            }
        } else {
            echo "Incorrect request";
        }
        exit;
    }

    private function getRoomRedirect(string $room_id): string
    {
        return $this->getBaseURI() . "room/" . $room_id;
    }

    private function getBaseURI(): string
    {
        return $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . "/";
    }

    private function getPlayerID(): string
    {
        $user_id = $this->getUid();
        $name = $_POST['playerName'];

        if (is_null($user_id) && !empty($name)) {
            $user_id = $this->getPlayerRepo()->createPlayer($name);
            $this->setUid($user_id);
        } else if (is_null($user_id) && empty($name)) {
            $this->errorService->setError('Player must have a name.');
        }

        return $user_id;
    }

    private function getValidatedAction()
    {
        $allowed_action = in_array($_POST['joinCreate'], self::$user_actions);
        if ($allowed_action) {
            return $_POST['joinCreate'];
        }

        echo "invalid selection";
        exit;
    }

    private function getRoomID(string $playerID, string $action): string
    {
        $room_id = empty($_POST['roomID']) ? null : $_POST['roomID'];

        if (is_null($room_id) && $action === "join") {
            $room_id = $this->getBoardRepo()->getOpenBoardID();
            if (is_null($room_id)) {
                $this->errorService->setError('No open rooms available');
            }
        }

        if (is_null($room_id) && $action === "create") {
            $room_id = $this->getBoardRepo()->createBoard($playerID);
        } else if (!is_null($room_id)) {
            $this->getBoardRepo()->joinBoard($room_id, $playerID);
        }

        return $room_id;
    }

    public function validatePlayerSession(string $room_id): bool
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