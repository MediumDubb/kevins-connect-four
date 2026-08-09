<?php

namespace MediumDubb\ConnectFour\Services;

use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Repositories\BoardRepo;
use MediumDubb\ConnectFour\Repositories\PlayerRepo;
use MediumDubb\ConnectFour\Repositories\TokenRepo;

class GameService
{
    private const string UID_KEY = 'UID';

    private ?BoardRepo $boardRepo;
    private ?PlayerRepo $playerRepo;
    private ?TokenRepo $tokenRepo;
    private ErrorService $errors;
    private SessionService $session;
    /**
     * Handle Game Logic for Controller
     */
    public function __construct(
        private readonly PDOConnector $db,
    ) {
        $this->errors = ErrorService::get();
        $this->boardRepo = new BoardRepo($this->db);
        $this->playerRepo = new PlayerRepo($this->db);
        $this->tokenRepo = new TokenRepo($this->db);
        $this->session = new SessionService();
    }

    public function getBoardStateObj(): array
    {
        $result = [];

        if ($room_id = $this->getSafeRoomID()) {
            $room_row = $this->boardRepo->getBoardState($room_id);
            if ($this->errors->isValid() && is_array($room_row)) {
                $result['result'] = ['data' => $this->getBoardPayload($room_row, $room_id)];
            } else {
                $result['result'] = ['error' => 'Something went wrong'];
            }
        }

        return $result;
    }

    public function getTokenFromPost(): array
    {
        return [
            "room_id"       => $_POST['room_id'],
            "player_id"     => $this->getUid() ?? $_POST['player_id'],
            "board_column"  => $_POST['board_column'],
        ];
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

    public function checkRoomID(): ?string
    {
        return $_POST['room_id'] ?? null;
    }

    public function handlePlayerTurn()
    {

    }

    private function validatePlayerTurn()
    {

    }

    private function setWinner(string $room_id, string $winnerId): void
    {
        $this->boardRepo->updateWinner($winnerId, $room_id);
    }

    private function rotatePlayer(string $room_id): void
    {
        $next_player_id = $this->boardRepo->alternatePlayerID($room_id);
        $this->boardRepo->updatePlayerTurn($room_id, $next_player_id);
    }

    private function checkForWinner(string $room_id): bool|string
    {
        $tokenObj = $this->getTokenFromPost();
        $tokens = $this->boardRepo->getTokensByBoardID($room_id);
        return $this->getWinnerId($tokens, $tokenObj);
    }

    private function getPlayerClass(string $player_id, string $room_id): string
    {
        return ($player_id === $this->boardRepo->getPlayerOne($room_id)) ? 'p1' : 'p2';
    }

    private function getBoardPayload(array $roomData, string $room_id): array
    {
        $player_id = $this->getUid();
        $tokens = $this->boardRepo->getTokensByBoardID($room_id);
        $player_class = $this->getPlayerClass($player_id, $room_id);
        $my_turn = ($player_id === $roomData['current_player_id']);
        $board_finished = ($roomData['board_finished'] !== 0);
        $room_ready = ($roomData['player_one_id'] && $roomData['player_two_id']);
        $winner_id = $roomData['winner_id'];

        return [
            "player_id"         => $player_id,
            "player_class"      => $player_class,
            "room_ready"        => $room_ready,
            "my_turn"           => $my_turn,
            "board_finished"    => $board_finished,
            "winner_id"         => $winner_id,
            "tokens"            => $tokens,
        ];
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

    /**
     * @param array $tokens
     * @param array $currentToken
     * @return bool|string
     * get current token index
     * check row forward/inverse
     * check col forward/inverse
     * check diag left forward/inverse
     * check diag right forward/inverse
     */
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

    /**
     * [  0,  1,  2,  3,  4,  5,  6 ]
     * [  7,  8,  9, 10, 11, 12, 13 ]
     * [ 14, 15, 16, 17, 18, 19, 20 ]
     * [ 21, 22, 23, 24, 25, 26, 27 ]
     * [ 28, 29, 30, 31, 32, 33, 34 ]
     * [ 35, 36, 37, 38, 39, 40, 41 ]
     *
     * We are trying to make a flat array of the whole board
     * this will be inversed from the board view for users, tokens will go from top down instead of bottom up
     * Once a flat structure is achieved we can use some simple math to check for a winner
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