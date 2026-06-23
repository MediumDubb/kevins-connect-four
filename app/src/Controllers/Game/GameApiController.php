<?php

namespace MediumDubb\ConnectFour\Controllers\Game;

use JetBrains\PhpStorm\NoReturn;
use MediumDubb\ConnectFour\Controllers\Core\CoreController;
use MediumDubb\ConnectFour\Repositories\BoardRepo;

class GameApiController extends CoreController
{
    public function dropToken()
    {
        // check if player turn
        // check if current board is full
            // if no to either
                // return error and a timeout
        // if proper player and board not full
            // create token
            // consume request to fill token row
    }

    // called after every action
    #[NoReturn]
    public function getBoardSate(): void
    {
        $result = null;
        if ($room_id = $this->getSafeRoomID()) {
            $room_row = new BoardRepo($this->db)->getBoardState($room_id);
            $room_row['tokens'] = new BoardRepo($this->db)->getBoardTokens($room_id);;
            $room_row['board_finished'] = ($room_row['board_finished'] !== 0);
            $room_row['player_id'] = $this->getUid();
            if ($this->errors->isValid() && is_array($room_row)) {
                $room_row['room_ready'] = ($room_row['player_one_id'] && $room_row['player_two_id']);
                $result['result'] = ['data' => $room_row];
            } else {
                $result['result'] = ['error' => 'Something went wrong'];
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode($result);
        exit;
    }

    private function getSafeRoomID(): ?string
    {
        $roomId = trim($_GET['room_id']) ?? null;

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
}