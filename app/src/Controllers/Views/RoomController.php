<?php

namespace MediumDubb\ConnectFour\Controllers\Views;

use JetBrains\PhpStorm\NoReturn;
use MediumDubb\ConnectFour\Controllers\Core\CoreController;
use MediumDubb\ConnectFour\Repositories\BoardRepo;
use MediumDubb\ConnectFour\Repositories\PlayerRepo;

class RoomController extends CoreController
{
    private static array $user_actions = [
        'join',
        'create'
    ];

    public function index(): void
    {
        require_once (dirname(__DIR__, 2) . "/Views/Entry.php");
    }

    public function gameRoom(string $id): void
    {
        // check if board exists
        // check if board is completed
        // check if current user session matches assigned players
        $valid_player = $this->validatePlayerSession($id);
        if ($valid_player) {
            require_once (dirname(__DIR__, 2) . "/Views/Room.php");
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
            $redirect_url = $this->getRoomRedirect($room_id);
            header("Location: $redirect_url");
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
            $player_repo = new PlayerRepo($this->db);
            $user_id = $player_repo->createPlayer($name);
            $this->setUid($user_id);
        } else if (is_null($user_id) && empty($name)) {
            $this->errors->setError('Player must have a name.');
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
        $board_repo = new BoardRepo($this->db);

        if (is_null($room_id) && $action === "join") {
            $room_id = $board_repo->getOpenBoardID();
            if (!is_null($room_id)) {
                $board_repo->joinBoard($room_id, $playerID);
            } else {
                $this->errors->setError('No open rooms available');
            }
        }

        if (is_null($room_id) && $action === "create") {
            $room_id = $board_repo->createBoard($playerID);
        } else {
            $board_repo->joinBoard($room_id, $playerID);
        }

        return $room_id;
    }

    public function validatePlayerSession(string $room_id): bool
    {
        if ($session_uid = $this->getUid()) {
            $board_users = new BoardRepo($this->db)->getRoomPlayerIDs($room_id);
            if (is_array($board_users)) {
                return in_array($session_uid, $board_users);
            }
        }

        return false;
    }

    private function createRoom()
    {
        // accepts empty POST
        // check for valid user ID in session
        // if none exist,
        // then create one
        // store char id in session
        // generate new board row
        // get board ID string

        // append to URL and redirect user to board
        // OR
        // save game state object in JS that's updated from server @ start of every turn
    }

    private function joinRoom()
    {
        // accepts GET with board ID
        // check for valid user ID in session
        // if none exist,
        // then create one
        // store char id in session

        // take ID provided and
        // append to URL and redirect user to board
        // OR
        // save game state object in JS that's updated from server @ start of every turn
    }
}