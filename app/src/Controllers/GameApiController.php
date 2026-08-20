<?php

namespace MediumDubb\ConnectFour\Controllers;

use JetBrains\PhpStorm\NoReturn;
use MediumDubb\ConnectFour\Domains\Board;
use MediumDubb\ConnectFour\DTO\BoardResponse;
use MediumDubb\ConnectFour\Exceptions\ApiException;
use MediumDubb\ConnectFour\Services\BoardCreateRequest;
use MediumDubb\ConnectFour\Services\BoardJoinRequest;
use MediumDubb\ConnectFour\Services\BoardStateRequest;
use MediumDubb\ConnectFour\Services\TokenHandler;

class GameApiController extends CoreController
{
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

        try {
            $tokenHandler = new TokenHandler($this->getUid());
            $responseObj = $tokenHandler->getResponseObj();
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
            $stateRequest = BoardStateRequest::fromQueryParams();
            $board = Board::getByID($stateRequest->getBoardID());
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
         *  1. validate request with user ID
         *  3. Create board in DB and retrieve the initial state
         *  5. Send response
         */
        try {
            $createRequest = BoardCreateRequest::validateRequestMethod($this->getUid());
            $board = Board::create($createRequest->getPlayerID());
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
            $joinRequest = BoardJoinRequest::fromQueryParams();
            $board = Board::getByJoin($joinRequest->getBoardID(), $this->getUid());
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
    private function JSONPayload(array $payload): void
    {
        $statusCode = $payload['status_code'] ?? 200;
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($payload);
        exit;
    }
}