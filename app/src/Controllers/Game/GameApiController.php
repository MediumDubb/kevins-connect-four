<?php

namespace MediumDubb\ConnectFour\Controllers\Game;

use JetBrains\PhpStorm\NoReturn;
use MediumDubb\ConnectFour\Controllers\Core\CoreController;
use MediumDubb\ConnectFour\Repositories\BoardRepo;
use MediumDubb\ConnectFour\Repositories\TokenRepo;

class GameApiController extends CoreController
{
    #[NoReturn]
    public function dropToken(): void
    {
        $room_id = $_POST['room_id'] ?? null;

        if (!is_null($room_id)) {
            $tokenObj = $this->getTokenObj();
            if (!is_null($tokenObj)) {
                $success = new TokenRepo($this->db)->setToken($tokenObj);
                if ($success) {
                    $boardRepo = new BoardRepo($this->db);
                    $next_player_id = $boardRepo->alternatePlayer($room_id);
                    $boardRepo->updatePlayerTurn($room_id, $next_player_id);
                    $this->getBoardSate();
                }
            }
        }
    }

    // called after every action
    #[NoReturn]
    public function getBoardSate(): void
    {
        $result = null;
        if ($room_id = $this->getSafeRoomID()) {
            $room_row = new BoardRepo($this->db)->getBoardState($room_id);
            if ($this->errors->isValid() && is_array($room_row)) {
                $result['result'] = ['data' => $this->getBoardStateObj($room_row, $room_id)];
            } else {
                $result['result'] = ['error' => 'Something went wrong'];
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode($result);
        exit;
    }

    private function getBoardStateObj(array $room_row, string $room_id): array
    {
        $player_id = $this->getUid();
        $tokens = new BoardRepo($this->db)->getBoardTokens($room_id);
        $playerClass = $this->getPlayerClass($player_id, $room_id);
        $my_turn = ($player_id === $room_row['current_player_id']);
        $board_finished = ($room_row['board_finished'] !== 0);
        $room_ready = ($room_row['player_one_id'] && $room_row['player_two_id']);

        return [
            "player_id" => $player_id,
            "player_class" => $playerClass,
            "room_ready" => $room_ready,
            "my_turn" => $my_turn,
            "board_finished" => $board_finished,
            "tokens" => [$tokens],
            "setting_token" => false,
        ];
    }

    private function getTokenObj(): ?array
    {
        if (isset($_POST)) {
            return [
                "room_id" => $_POST['room_id'],
                "player_id" => $this->getUid() ?? $_POST['player_id'],
                "board_column" => $_POST['board_column'],
            ];
        }

        return null;
    }

    private function getSafeRoomID(): ?string
    {
        $roomId = isset($_GET['room_id']) ? trim($_GET['room_id']) : null;

        if (is_null($roomId) && isset($_POST)) {
            $roomId = isset($_POST['room_id']) ? trim($_POST['room_id']) : null;
        }

        if (!is_string($roomId)) {
            // Missing or invalid type
            $this->errors->setError('Invalid room ID.');
            return null;
        }

        if ($roomId === '') {
            $this->errors->setError('Missing room ID.');
            return null;
        }

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $roomId)) {
            $this->errors->setError('Invalid room ID format.');
            return null;
        }

        return $roomId;
    }

    private function getPlayerClass(string $player_id, string $room_id): string
    {
        return ($player_id === new BoardRepo($this->db)->getPlayerOne($room_id)) ? 'p1' : 'p2';
    }
}