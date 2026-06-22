<?php

namespace MediumDubb\ConnectFour\Controllers\Game;

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
    public function getBoardSate(): string
    {
        $result = null;
        if ($room_id = $this->getSafeRoomID()) {
            $room_row = new BoardRepo($this->db)->getBoardState($room_id);
            if (!$this->errors->isValid()) {
                $result['response'] = ['errors' => $this->errors->getErrorsList()];
            } else if (is_array($room_row)) {
                $result['response'] = ['data' => $room_row];
            } else {
                $result['response'] = ['errors' => 'Something went wrong'];
            }
        }

        return json_encode($result);
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