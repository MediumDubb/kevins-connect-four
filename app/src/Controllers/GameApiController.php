<?php

namespace MediumDubb\ConnectFour\Controllers;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use MediumDubb\ConnectFour\Exceptions\ApiException;
use PDOException;

class GameApiController extends CoreController
{
    private ?string $playerID;
    private ?string $roomID;
    private ?string $column;
    private bool $has_error = false;

    private static array $user_actions = [
        'join',
        'create'
    ];

    private array $response_payload = [
        'status_code' => null,
        'id' => null,
        'current_player' => null,
        'winner' => null,
        'player1' => null,
        'player2' => null,
        'tokens' => null,
    ];

    private array $err_response_payload = [
        'status_code' => null,
        'error_message' => null
    ];

    public function __construct(){
        parent::__construct();
        $this->playerID = $this->getUid();
        $this->roomID = $this->getSafeBoardID();
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

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->clearUid(); //test

        } else {
            $this->errorService->setError("Invalid request");
        }
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
        /**
         *  1. validate request
         *      a. extract boardID -> getSafeBoardID()
         *      b. check that boardID is valid -> getBoardByID()
         *  2. Retrieve board state -> Get result from successful check
         *  3. Send response
         */

        try {
            $this->checkMethod('GET');
            $boardID = $this->getSafeBoardID();
            $rowData = $this->getBoardRepo()->getBoardByID($boardID);
            $responseObj = $this->getResponsePayload($rowData);
        } catch (ApiException $e) {
            $obj = [
                'status_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ];

            $responseObj = $this->getResponsePayload($obj);
        }

        $this->JSONPayload($responseObj);
    }

    #[NoReturn]
    public function create(): void
    {
        /**
         *  1. validate request
         *  2. Create board in DB
         *   2. Check user
         *       a. Make user if they don't already exist
         *  3. Retrieve board state
         *  2. Send response
         */
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
        /**
         *  1. validate request
         *      a. extract boardID
         *      b. check that boardID is valid
         *  2. Check user
         *      a. Make user if they don't already exist
         *  2. Retrieve board state
         *  3. Send response
         */
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

    /**
     * @throws ApiException
     */
    private function checkMethod(string $method): true
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            throw new ApiException("InvalidRequest", "Invalid request method", 405);
        }

        return true;
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
        http_response_code($payload['status_code']);
        echo json_encode($payload);
        exit;
    }

    private function getSafeBoardID(): ?string
    {
        $boardID = isset($_GET['boardID']) ? trim($_GET['boardID']) : null;

        if ($boardID === '' || $boardID === null ) {
            $this->errorService->setError('Missing board ID.');
            return null;
        }

        if (!preg_match('/\d+/', $boardID) || $boardID === '0') {
            $this->errorService->setError('Invalid board ID.');
            return null;
        }

        return $boardID;
    }

    private function getResponsePayload(array $data): array
    {
        if ($this->has_error) {
            $this->err_response_payload['status_code'] = $data['status_code'];
            $this->err_response_payload['error_message'] = $data['error_message'];
            return $this->err_response_payload;
        }

        $this->response_payload['status_code'] = 200;
        $this->response_payload['id'] = $data['id'];
        $this->response_payload['current_player'] = $data['current_player'];
        $this->response_payload['winner'] = $data['winner'];
        $this->response_payload['player1'] = $data['boardID'];
        $this->response_payload['player2'] = $data['boardID'];
        $this->response_payload['tokens'] = $data['boardID'];
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

    private function createNewUser(): ?string
    {
        try {
            $userID = $this->getPlayerRepo()->getNewPlayerID();
            $this->setUid($userID);
            return $userID;
        } catch (PDOException|Exception $e) {
            $this->errorService->setError('Something went wrong: ' . $e->getMessage());
        }

        return null;
    }
}