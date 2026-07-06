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
            $boardRepo = new BoardRepo($this->db);
            $tokenObj = $this->getTokenObj();
            if (!is_null($tokenObj)) {
                if ($winnerId = $this->getWinnerId($boardRepo->getBoardTokens($room_id), $tokenObj)) {
                    $boardRepo->updateWinner($winnerId, $room_id);
                } else {
                    $next_player_id = $boardRepo->alternatePlayer($room_id);
                    $boardRepo->updatePlayerTurn($room_id, $next_player_id);
                }
                $success = new TokenRepo($this->db)->setToken($tokenObj);
                if ($success) {
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
        $winner_id = $room_row['winner_id'];

        return [
            "player_id" => $player_id,
            "player_class" => $playerClass,
            "room_ready" => $room_ready,
            "my_turn" => $my_turn,
            "board_finished" => $board_finished,
            "winner_id" => $winner_id,
            "tokens" => $tokens,
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

        if (is_null($roomId) && isset($_POST['room_id'])) {
            $roomId = $_POST['room_id'];
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

    private function getWinnerId(array $tokens, array $currentToken): bool|string
    {
        // after 7 tokens have been placed, check all diagonal, column, and row options for last token placed
        $player_id = $this->getUid();
        $tokens[] = $currentToken;
        if (count($tokens) >= 7) {
            $boardArray = $this->getFlatTokenArray($tokens, (count($tokens) - 1));
            $currentTokenIndex = null;
            foreach ($boardArray as $token_index => $token_data) {
                if ($token_data !== null && $token_data['last_move']) {
                    $currentTokenIndex = $token_index;
                    break;
                }
            }

            // row forward/inverse
            // col forward/inverse
            // diag left forward/inverse
            // diag right forward/inverse

            if (
                ($currentTokenIndex >= 0 && $currentTokenIndex <= 41) &&
                ($currentTokenIndex - 1 >= 0 && $currentTokenIndex - 1 <= 41) &&
                ($currentTokenIndex - 2 >= 0 && $currentTokenIndex - 2 <= 41) &&
                ($currentTokenIndex - 3 >= 0 && $currentTokenIndex - 3 <= 41) &&
                $boardArray[$currentTokenIndex] !== null &&
                $boardArray[$currentTokenIndex - 1] !== null &&
                $boardArray[$currentTokenIndex - 2] !== null &&
                $boardArray[$currentTokenIndex - 3] !== null &&
                ($boardArray[$currentTokenIndex]['player_id'] === $boardArray[$currentTokenIndex - 1]['player_id']) &&
                ($boardArray[$currentTokenIndex - 1]['player_id'] === $boardArray[$currentTokenIndex - 2]['player_id']) &&
                ($boardArray[$currentTokenIndex - 2]['player_id'] === $boardArray[$currentTokenIndex - 3]['player_id'])
            ) {
                return $player_id;
            }

            if (
                ($currentTokenIndex >= 0 && $currentTokenIndex <= 41) &&
                ($currentTokenIndex + 1 >= 0 && $currentTokenIndex + 1 <= 41) &&
                ($currentTokenIndex + 2 >= 0 && $currentTokenIndex + 2 <= 41) &&
                ($currentTokenIndex + 3 >= 0 && $currentTokenIndex + 3 <= 41) &&
                $boardArray[$currentTokenIndex] !== null &&
                $boardArray[$currentTokenIndex + 1] !== null &&
                $boardArray[$currentTokenIndex + 2] !== null &&
                $boardArray[$currentTokenIndex + 3] !== null &&
                ($boardArray[$currentTokenIndex]['player_id'] === $boardArray[$currentTokenIndex + 1]['player_id']) &&
                ($boardArray[$currentTokenIndex + 1]['player_id'] === $boardArray[$currentTokenIndex + 2]['player_id']) &&
                ($boardArray[$currentTokenIndex + 2]['player_id'] === $boardArray[$currentTokenIndex + 3]['player_id'])
            ) {
                return $player_id;
            }

            if (
                ($currentTokenIndex >= 0 && $currentTokenIndex <= 41) &&
                ($currentTokenIndex - 7 >= 0 && $currentTokenIndex - 7 <= 41) &&
                ($currentTokenIndex - 14 >= 0 && $currentTokenIndex - 14 <= 41) &&
                ($currentTokenIndex - 21 >= 0 && $currentTokenIndex - 21 <= 41) &&
                $boardArray[$currentTokenIndex] !== null &&
                $boardArray[$currentTokenIndex - 7] !== null &&
                $boardArray[$currentTokenIndex - 14] !== null &&
                $boardArray[$currentTokenIndex - 21] !== null &&
                ($boardArray[$currentTokenIndex]['player_id'] === $boardArray[$currentTokenIndex - 7]['player_id']) &&
                ($boardArray[$currentTokenIndex - 7]['player_id'] === $boardArray[$currentTokenIndex - 14]['player_id']) &&
                ($boardArray[$currentTokenIndex - 14]['player_id'] === $boardArray[$currentTokenIndex - 21]['player_id'])
            ) {
                return $player_id;
            }

            if (
                ($currentTokenIndex >= 0 && $currentTokenIndex <= 41) &&
                ($currentTokenIndex + 7 >= 0 && $currentTokenIndex + 7 <= 41) &&
                ($currentTokenIndex + 14 >= 0 && $currentTokenIndex + 14 <= 41) &&
                ($currentTokenIndex + 21 >= 0 && $currentTokenIndex + 21 <= 41) &&
                $boardArray[$currentTokenIndex] !== null &&
                $boardArray[$currentTokenIndex + 7] !== null &&
                $boardArray[$currentTokenIndex + 14] !== null &&
                $boardArray[$currentTokenIndex + 21] !== null &&
                ($boardArray[$currentTokenIndex]['player_id'] === $boardArray[$currentTokenIndex + 7]['player_id']) &&
                ($boardArray[$currentTokenIndex + 7]['player_id'] === $boardArray[$currentTokenIndex + 14]['player_id']) &&
                ($boardArray[$currentTokenIndex + 14]['player_id'] === $boardArray[$currentTokenIndex + 21]['player_id'])
            ) {
                return $player_id;
            }

            if (
                ($currentTokenIndex >= 0 && $currentTokenIndex <= 41) &&
                ($currentTokenIndex - 8 >= 0 && $currentTokenIndex - 8 <= 41) &&
                ($currentTokenIndex - 16 >= 0 && $currentTokenIndex - 16 <= 41) &&
                ($currentTokenIndex - 24 >= 0 && $currentTokenIndex - 24 <= 41) &&
                $boardArray[$currentTokenIndex] !== null &&
                $boardArray[$currentTokenIndex - 8] !== null &&
                $boardArray[$currentTokenIndex - 16] !== null &&
                $boardArray[$currentTokenIndex - 24] !== null &&
                ($boardArray[$currentTokenIndex]['player_id'] === $boardArray[$currentTokenIndex - 8]['player_id']) &&
                ($boardArray[$currentTokenIndex - 8]['player_id'] === $boardArray[$currentTokenIndex - 16]['player_id']) &&
                ($boardArray[$currentTokenIndex - 16]['player_id'] === $boardArray[$currentTokenIndex - 24]['player_id'])
            ) {
                return $player_id;
            }

            if (
                ($currentTokenIndex >= 0 && $currentTokenIndex <= 41) &&
                ($currentTokenIndex + 8 >= 0 && $currentTokenIndex + 8 <= 41) &&
                ($currentTokenIndex + 16 >= 0 && $currentTokenIndex + 16 <= 41) &&
                ($currentTokenIndex + 24 >= 0 && $currentTokenIndex + 24 <= 41) &&
                $boardArray[$currentTokenIndex] !== null &&
                $boardArray[$currentTokenIndex + 8] !== null &&
                $boardArray[$currentTokenIndex + 16] !== null &&
                $boardArray[$currentTokenIndex + 24] !== null &&
                ($boardArray[$currentTokenIndex]['player_id'] === $boardArray[$currentTokenIndex + 8]['player_id']) &&
                ($boardArray[$currentTokenIndex + 8]['player_id'] === $boardArray[$currentTokenIndex + 16]['player_id']) &&
                ($boardArray[$currentTokenIndex + 16]['player_id'] === $boardArray[$currentTokenIndex + 24]['player_id'])
            ) {
                return $player_id;
            }

        }

        return false;
    }

    /*
        [  0,  1,  2,  3,  4,  5,  6 ]
        [  7,  8,  9, 10, 11, 12, 13 ]
        [ 14, 15, 16, 17, 18, 19, 20 ]
        [ 21, 22, 23, 24, 25, 26, 27 ]
        [ 28, 29, 30, 31, 32, 33, 34 ]
        [ 35, 36, 37, 38, 39, 40, 41 ]
        */
    private static function getFlatTokenArray(array $tokens, int $lastMoveKey): array
    {
        $newArray = [];
        for ($i = 0; $i < 42; $i++) {
            if ($i % 7 === 0) {
                // col 1
                $newArray[$i] = null;
                foreach ($tokens as $key => $token) {
                    if ($token['board_column'] == 0) {
                        $lastMove = $key === $lastMoveKey;
                        $newArray[$i] = [ 'player_id' => $token['player_id'], 'last_move' => $lastMove ];
                        unset($tokens[$key]);
                        break;
                    }
                }
            }
            if ($i % 7 === 1) {
                // col 2
                $newArray[$i] = null;
                foreach ($tokens as $key => $token) {
                    if ($token['board_column'] == 1) {
                        $lastMove = $key === $lastMoveKey;
                        $newArray[$i] = [ 'player_id' => $token['player_id'], 'last_move' => $lastMove ];
                        unset($tokens[$key]);
                        break;
                    }
                }
            }
            if ($i % 7 === 2) {
                // col 3
                $newArray[$i] = null;
                foreach ($tokens as $key => $token) {
                    if ($token['board_column'] == 2) {
                        $lastMove = $key === $lastMoveKey;
                        $newArray[$i] = [ 'player_id' => $token['player_id'], 'last_move' => $lastMove ];
                        unset($tokens[$key]);
                        break;
                    }
                }
            }
            if ($i % 7 === 3) {
                // col 4
                $newArray[$i] = null;
                foreach ($tokens as $key => $token) {
                    if ($token['board_column'] == 3) {
                        $lastMove = $key === $lastMoveKey;
                        $newArray[$i] = [ 'player_id' => $token['player_id'], 'last_move' => $lastMove ];
                        unset($tokens[$key]);
                        break;
                    }
                }
            }
            if ($i % 7 === 4) {
                // col 5
                $newArray[$i] = null;
                foreach ($tokens as $key => $token) {
                    if ($token['board_column'] == 4) {
                        $lastMove = $key === $lastMoveKey;
                        $newArray[$i] = [ 'player_id' => $token['player_id'], 'last_move' => $lastMove ];
                        unset($tokens[$key]);
                        break;
                    }
                }
            }
            if ($i % 7 === 5) {
                // col 6
                $newArray[$i] = null;
                foreach ($tokens as $key => $token) {
                    if ($token['board_column'] == 5) {
                        $lastMove = $key === $lastMoveKey;
                        $newArray[$i] = [ 'player_id' => $token['player_id'], 'last_move' => $lastMove ];
                        unset($tokens[$key]);
                        break;
                    }
                }
            }
            if ($i % 7 === 6) {
                // col 7
                $newArray[$i] = null;
                foreach ($tokens as $key => $token) {
                    if ($token['board_column'] == 6) {
                        $lastMove = $key === $lastMoveKey;
                        $newArray[$i] = [ 'player_id' => $token['player_id'], 'last_move' => $lastMove ];
                        unset($tokens[$key]);
                        break;
                    }
                }
            }
        }

        return $newArray;
    }
}