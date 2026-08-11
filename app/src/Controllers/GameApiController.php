<?php

namespace MediumDubb\ConnectFour\Controllers;

use JetBrains\PhpStorm\NoReturn;

class GameApiController extends CoreController
{
    private ?int $playerID;
    private ?int $roomID;

    private static array $user_actions = [
        'join',
        'create'
    ];

    private array $response_payload = [
        'data' => null,
        'error_message' => null
    ];

    public function __construct(){
        parent::__construct();
        $this->playerID = $this->getUid();
        $this->roomID = $this->getSafeRoomID();
    }

    // todo Fix bug with win condition not triggering on first possible chance to win (probably wrong token index)

    #[NoReturn]
    public function dropToken(): void
    {
        /**
         *  1. validate request
         *      a. check for all required data
         *      b. check request is from the correct player
         *      c. make sure the move is valid
         *  2. Set the token
         *  2. check for winner
         *      a. if found
         *          ia. update winner
         *      b. if not found
         *          ib. do not update player turns
         *  4. send updated board state
         */
        $room_id = $_POST['room_id'] ?? null;

        if (!is_null($room_id)) {
            $tokenObj = $this->getTokenObj();
            if (!is_null($tokenObj)) {
                // check for a winner. If one exists, then we do not want to update the players
                if ($winnerId = $this->getWinnerId($this->getBoardRepo()->getTokensByBoardID($room_id), $tokenObj)) {
                    $this->getBoardRepo()->updateWinner($winnerId, $room_id);
                } else {
                    $next_player_id = $this->getBoardRepo()->getAlternatePlayerID($room_id);
                    $this->getBoardRepo()->updatePlayerTurn($room_id, $next_player_id);
                }

                if ($this->getTokenRepo()->setToken($tokenObj)) {
                    $this->getBoardSate();
                }
            }
        }
    }

    // used for all responses
    #[NoReturn]
    public function getBoardSate(): void
    {
        $roomData = $this->roomID === null
            ? false
            : $this->getBoardRepo()->getBoardState($this->roomID);

        $this->prepareStatePayload($roomData);

        $this->JSONPayload($this->getResponsePayload());
    }

    #[NoReturn]
    public function create(): void
    {
        $location = "/";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->clearUid(); //test

            $playerID = $this->setUser();
            $location = $this->getLocation($playerID);
        } else {
            $this->errorService->setError("Invalid request");
        }

        $this->getBoardSate();
    }

    #[NoReturn]
    public function join(): void
    {
        $location = "/";

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->clearUid(); //test

            $result = $this->validateFormSubmission();

            if (!$result['valid']) {
                $playerID = $this->setUser();
                $location = $this->getLocation($playerID);
            }
        } else {
            $this->errorService->setError("Invalid request");
        }

        $this->getBoardSate();
    }

    private function getBoardStateObj(array $room_row): array
    {
        $tokens = $this->getBoardRepo()->getTokensByBoardID($this->roomID);
        $player_class = $this->getPlayerClass($this->playerID, $this->roomID);
        $my_turn = ($this->playerID === $room_row['current_player_id']);
        $board_finished = ($room_row['board_finished'] !== 0);
        $room_ready = ($room_row['player_one_id'] && $room_row['player_two_id']);
        $winner_id = $room_row['winner_id'];

        return [
            "player_id"         => $this->playerID,
            "player_class"      => $player_class,
            "room_ready"        => $room_ready,
            "my_turn"           => $my_turn,
            "board_finished"    => $board_finished,
            "winner_id"         => $winner_id,
            "tokens"            => $tokens,
        ];
    }

    private function getPreparedTokenObj(array $token_row): array
    {
        if ($this->validateTokenData()) {

        } else {
            $this->errorService->setError('Invalid token data.');
        }
    }

    private function validateTokenData(): bool
    {
        $column = $_GET['board_column'] ?? null;

        return ($column && $this->roomID && $this->playerID);
    }

    private function prepareStatePayload(array|bool $roomData): void
    {
        if (isset($roomData['excpetion_error_message'])) {
            $this->errorService->setError($roomData['excpetion_error_message']);
        }

        if (!$roomData) {
            $this->errorService->setError('Room ID not found or empty.');
        }

        if ($this->errorService->isValid()) {
           $this->setResponsePayload($this->getBoardStateObj($roomData));
        } else {
            $this->setResponsePayload(null, $this->errorService->getErrorsList());
        }
    }

    private function getTokenObj(): ?array
    {
        if (isset($_POST)) {
            return [
                "room_id"       => $_POST['room_id'],
                "player_id"     => $this->getUid() ?? $_POST['player_id'],
                "board_column"  => $_POST['board_column'],
            ];
        }

        return null;
    }

    #[NoReturn]
    private function JSONPayload(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode($payload);
        exit;
    }

    private function getSafeRoomID(): ?string
    {
        $roomId = isset($_GET['room_id']) ? trim($_GET['room_id']) : null;

        if (is_null($roomId) && isset($_POST['room_id'])) {
            $roomId = trim($_POST['room_id']);
        }

        if (!is_string($roomId)) {
            // Missing or invalid type
            $this->errorService->setError('Invalid room ID.');
            return null;
        }

        if ($roomId === '') {
            $this->errorService->setError('Missing room ID.');
            return null;
        }

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $roomId)) {
            $this->errorService->setError('Invalid room ID format.');
            return null;
        }

        return $roomId;
    }

    private function setResponsePayload(?array $data = null, ?array $errors = null): void
    {
        $this->response_payload['data'] = $data;
        $this->response_payload['errors'] = $errors;
    }

    private function getResponsePayload(): array
    {
        return $this->response_payload;
    }

    private function getPlayerClass(string $player_id, string $room_id): string
    {
        return ($player_id === $this->getBoardRepo()->getPlayerOne($room_id)) ? 'p1' : 'p2';
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

    private function setUser(): ?string
    {
        try {
            $userID = $this->getPlayerRepo()->createPlayer();
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

    private function getRoomID(string $playerID, string $action): ?string
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
}