<?php

namespace MediumDubb\ConnectFour\Controllers;

use JetBrains\PhpStorm\NoReturn;
use MediumDubb\ConnectFour\Domains\Board;
use MediumDubb\ConnectFour\DTO\BoardResponse;
use MediumDubb\ConnectFour\Exceptions\ApiException;

class GameApiController extends CoreController
{

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
        $responseObj = [];

        try {
            $this->checkMethod('GET');
            $boardID = $this->getSafeBoardID();
            $tokenObj = $this->getTokenObj();
            if ($winnerId = $this->getWinnerId($this->getBoardRepo()->getTokensByBoardID($boardID), $tokenObj)) {
                $this->getBoardRepo()->updateWinner($winnerId, $boardID);
            } else {
                $next_player_id = $this->getBoardRepo()->getAlternatePlayerID($boardID);
                $this->getBoardRepo()->updatePlayerTurn($boardID, $next_player_id);
            }
            $this->getTokenRepo()->setToken($tokenObj);
            $this->getBoardSate();
        } catch (ApiException $e) {
            $this->err_response_payload['status_code'] = $e->getCode();
            $this->err_response_payload['error_message'] = $e->getMessage();
            $responseObj = $this->err_response_payload;
        }

        $this->JSONPayload($responseObj);
    }

    // used for all responses
    #[NoReturn]
    public function getBoardSate(): void
    {
        /**
         *  1. validate request
         *      a. extract boardID -> getSafeBoardID()
         *      b. check that boardID is valid -> getBoardByID()
         *          b1. board exists
         *          b2. board is not complete
         *  2. Retrieve board state -> Get result from successful check
         *  3. Send response
         */

        try {
            $this->checkMethod('GET');
            $boardID = $this->getSafeBoardID();
            $board = $this->getBoardRepo()->getBoardByID($boardID);
            $boardResponse = BoardResponse::fromDomain($board);
            $responseObj = $boardResponse->toArray();
        } catch (ApiException $e) {
            $this->err_response_payload['status_code'] = $e->getCode();
            $this->err_response_payload['error_message'] = $e->getMessage();
            $responseObj = $this->err_response_payload;
        }

        $this->JSONPayload($responseObj);
    }

    #[NoReturn]
    public function create(): void
    {
        /**
         *  1. validate request
         *  2. get user ID
         *  3. Create board in DB
         *  4. Retrieve board state
         *  5. Send response
         */
        try {
            $this->checkMethod('POST');
            $playerID = $this->getUid();
            $board = $this->getBoardRepo()->create($playerID);
            $boardResponse = BoardResponse::fromDomain($board);
            $responseObj = $boardResponse->toArray();
        } catch (ApiException $e) {
            $this->err_response_payload['status_code'] = $e->getCode();
            $this->err_response_payload['error_message'] = $e->getMessage();
            $responseObj = $this->err_response_payload;
        }

        $this->JSONPayload($responseObj);
    }

    #[NoReturn]
    public function join(): void
    {
        /**
         *  1. validate request
         *      a. extract boardID
         *      b. check that boardID is valid
         *  2. Check user
         *      a. if the board does not have an open spot, or the UID does not match any current players then reject their join with an error
         *  2. Retrieve board state
         *  3. Send response
         */
        try {
            $this->checkMethod('GET');
            $boardID = $this->getSafeBoardID();
            $rowData = $this->getBoardRepo()->getBoardByID($boardID);
            $this->isBoardCompleted($rowData);
            $responseObj = $this->getStatePayload($rowData);
        } catch (ApiException $e) {
            $this->err_response_payload['status_code'] = $e->getCode();
            $this->err_response_payload['error_message'] = $e->getMessage();
            $responseObj = $this->err_response_payload;
        }

        $this->JSONPayload($responseObj);
    }

    /**
     * @throws ApiException
     */
    private function checkMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            throw new ApiException("InvalidRequest", "Invalid request method", 405);
        }
    }

    /**
     * @throws ApiException
     */
    private function getTokenObj(): array
    {
        $boardID = $this->getSafeBoardID();
        $playerID = $this->getUid();

        if (!empty($boardID) &&!empty($_GET['column'])) {
            return [
                "room_id"       => $boardID,
                "player_id"     => $playerID,
                "board_column"  => $_GET['column'],
            ];
        } else {
            throw new ApiException("InvalidRequest", "Incomplete token request", 400);
        }
    }

    #[NoReturn]
    private function JSONPayload(array $payload): void
    {
        $statusCode = $payload['status_code'] ?? 200;
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($payload);
        exit;
    }

    /**
     * @throws ApiException
     */
    private function isBoardCompleted(?array $rowData): void
    {
        if (is_array($rowData) && isset($rowData['winner'])) {
            throw new ApiException("InvalidRequest", "The requested board is a completed game", 400);
        } else if (!is_array($rowData)) {
            throw new ApiException("InvalidRequest", "Completion check failed.", 400);
        }
    }

    /**
     * @throws ApiException
     */
    private function getSafeBoardID(): ?string
    {
        $boardID = isset($_GET['boardID']) ? trim($_GET['boardID']) : null;

        if ($boardID === '' || $boardID === null ) {
            throw new ApiException("InvalidRequest", "Missing board ID", 400);
        }

        if (!preg_match('/\d+/', $boardID) || $boardID === '0') {
            throw new ApiException("InvalidRequest", "Invalid board ID", 400);
        }

        return $boardID;
    }

    /**
     * @throws ApiException
     */
    private function getStatePayload(Board $boardData): Array
    {
        /**
         * "Current player" and "tokens" are not retrieved from the board row data in the DB
         * These two values are added as a seperate request from the DB
         */
        if (
            isset($boardData["id"]) &&
            isset($boardData["player1"]) &&
            isset($boardData["created_at"])
        ) {
            $this->response_payload['status_code'] = 200;
            $this->response_payload['id'] = $boardData['id'];
            $this->response_payload['current_player'] = $boardData['current_player'];
            $this->response_payload['player1'] = $boardData['player1'];
            $this->response_payload['player2'] = $boardData['player2'];
            $this->response_payload['winner'] = $boardData['winner'];
            $this->response_payload['tokens'] = $boardData['tokens'];
            return $this->response_payload;
        } else {
            throw new ApiException("InvalidRequest", "Invalid payload", 400);
        }
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