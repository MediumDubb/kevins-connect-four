<?php

namespace MediumDubb\ConnectFour\Controllers;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use PDOException;

class EntryController extends CoreController
{
    private static array $user_actions = [
        'join',
        'create'
    ];

    public function index(?string $id = null): void
    {
        if (is_null($id)) {
            require_once (dirname(__DIR__) . "/Views/Entry.php");
        } else if ($valid_player = $this->validatePlayerSession($id)) {
            require_once (dirname(__DIR__) . "/Views/Room.php");
        } else {
            $this->errorService->setError("You don't belong to that game");
            header("location: {$this->getBaseURI()}");
        }
    }

    #[NoReturn]
    public function initGame(): void
    {
        $location = "/";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->clearUid(); //test

            $result = $this->validateFormSubmission();

            if (!$result['valid']) {
                $playerID = $this->setUser($result['userName']);
                $location = $this->getLocation($playerID);
            }
        } else {
            $this->errorService->setError("Invalid request");
        }

        header("Location: $location");
    }

    private function getLocation($playerID): string
    {
        $action = $this->getValidatedAction();

        if ($action && $playerID && $room_id = $this->getRoomID($playerID, $action)) {
            return $this->getRoomPath($room_id);
        }

        return '/';
    }

    private function validateFormSubmission(): array
    {
        if (!isset($_POST['userName']) || empty($_POST['userName'])) {
            $this->errorService->setError("You must give yourself a name");
            return [ 'valid' => false, 'userName' => null];
        }

        if ( $this->getUid() !== null ) {
            $this->errorService->setError("You are already an assigned player");
            return [ 'valid' => false, 'userName' => null];
        }

        return [ 'valid' => true, 'userName' => $_POST['userName']];
    }

    private function getRoomPath(string $room_id): string
    {
        return $this->getBaseURI() . "room/" . $room_id;
    }

    private function getBaseURI(): string
    {
        return $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . "/";
    }

    private function setUser(string $name): ?string
    {
        try {
            $userID = $this->getPlayerRepo()->createPlayer($name);
            $this->setUid($userID);
            return $userID;
        } catch (PDOException|Exception $e) {
            $this->errorService->setError('Something went wrong: ' . $e->getMessage());
        }

        return null;
    }

    private function getValidatedAction(): ?string
    {
        $allowed_action = in_array($_POST['joinCreate'], self::$user_actions);

        if ($allowed_action) {
            return $_POST['joinCreate'];
        }

        $this->errorService->setError("invalid selection");
        return null;
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