<?php

namespace MediumDubb\ConnectFour\Services;

use MediumDubb\ConnectFour\Domains\Board;
use MediumDubb\ConnectFour\Domains\Token;
use MediumDubb\ConnectFour\DTO\BoardResponse;
use MediumDubb\ConnectFour\Exceptions\ApiException;
use MediumDubb\ConnectFour\Repositories\BoardRepo;
use MediumDubb\ConnectFour\Repositories\TokenRepo;

final readonly class TokenHandler
{
    private Token $currentToken;
    private Board $board;

    /**
     * @throws ApiException
     */
    public function __construct(private string|int $sessionPlayerID)
    {
        $request = TokenDropRequest::fromQueryParams();
        $this->currentToken = $request->getCurrentToken();
        $this->board = $request->getBoardFromRequest();
    }

    /**
     * @throws ApiException
     */
    public function getResponseObj(): array
    {
//        $this->validatePlayerMove();
        $this->setCurrentToken();
        $winnerID = $this->getWinner();
        if ($winnerID) {
            $this->board->setWinner($winnerID);
            $newBoard = new BoardRepo()->getBoardByID($this->board->getBoardID());
            return BoardResponse::fromDomain($newBoard)->toArray();
        }

        return BoardResponse::fromDomain($this->board)->toArray();
    }

    /**
     * @throws ApiException
     */
    private function setCurrentToken(): void
    {
        new TokenRepo()->setToken($this->currentToken->getBoardID(), $this->currentToken->getPlayerID(), $this->currentToken->getBoardColumn(), $this->currentToken->getBoardRow());
    }

    /**
     * @throws ApiException
     */
    private function validatePlayerMove(): void
    {
        $sessionPlayerID = $this->sessionPlayerID;
        $requestPlayerID = $this->currentToken->getPlayerID();
        $currentPlayerID = $this->board->getCurrentPlayer();

        if ($sessionPlayerID !== $requestPlayerID)
            throw new ApiException('InvalidPlayerMove', 'Do not make moves for other players', 400);

        if ($currentPlayerID !== $requestPlayerID)
            throw new ApiException('InvalidPlayerMove', 'It\'s not your turn', 400);

        if ($this->currentToken->getBoardRow() >= 5) // 0-5 | 6 total
            throw new ApiException('InvalidTokenPlacement', 'Invalid token placement, column is full', 400);
    }

    /**
     * @throws ApiException
     */
    private function getWinner(): bool|int
    {
        $currentRow = $this->currentToken->getBoardRow();
        $currentColumn = $this->currentToken->getBoardColumn();
        $skipLeft = $this->skipCheck([$currentRow, $currentColumn]);
        $skipRight = $this->skipCheck([$currentRow, $currentColumn], false);
        $playerID = $this->currentToken->getPlayerID();
        $boardRows = $this->board->getBoardAsArray();
        $connectCounter = 0;
        $winner = false;

        // vertical - from last drop check if vertical is at least 4 rows from bottom, count concurrent player ID's and see if four match in a row
        if ($currentRow <= 3) {
            for ($i = 0; $i < $currentRow; $i++) {
                $connectCounter = ($boardRows[$currentRow + $i][$currentColumn] === $playerID) ? $connectCounter + 1 : 0;
                $winner = ($connectCounter === 4);
            }
            $connectCounter = 0;
        }

        // horizontal - on same row as current token, start at beginning and count concurrent player ID's and see if four match in a row
        if (!$winner) {
            for ($i = 0; $i < $this->board->cols; $i++) {
                $connectCounter = ($boardRows[$currentRow][$i] === $playerID) ? $connectCounter + 1 : 0;
                $winner = ($connectCounter === 4);
            }
            $connectCounter = 0;
        }

        // left horizontal - find topmost cord and traverse diagonal in downward left direction counting concurrent player ID's and see if four match in a row
        if (!$winner && !$skipLeft) {
            $topLeftDownCord = $this->getTopLeftCord($currentRow, $currentColumn);
            $newCurrRow = $topLeftDownCord[0];
            $newCurrCol = $topLeftDownCord[1];
            for ($i = 0; $i < 5; $i++) {
                $connectCounter = ($boardRows[$newCurrRow][$newCurrCol] === $playerID) ? $connectCounter + 1 : 0;
                $winner = ($connectCounter === 4);
                $newCurrRow++;
                $newCurrCol++;
                if ($newCurrRow > 5 || $newCurrCol > 6) { break; }
            }
            $connectCounter = 0;
        }

        // right horizontal - find topmost cord and traverse diagonal in downward right direction counting concurrent player ID's and see if four match in a row
        if (!$winner && !$skipRight) {
            $topRightDownCord = $this->getTopRightCord($currentRow, $currentColumn);
            $newCurrRow = $topRightDownCord[0];
            $newCurrCol = $topRightDownCord[1];
            for ($i = 0; $i < 5; $i++) {
                $connectCounter = ($boardRows[$newCurrRow][$newCurrCol] === $playerID) ? $connectCounter + 1 : 0;
                $winner = ($connectCounter === 4);
                $newCurrRow++;
                $newCurrCol--;
                if ($newCurrRow > 5 || $newCurrCol < 0) { break; }
            }
            $connectCounter = 0;
        }

//        [
//            [0,1,2,3,4,5,6,7],  row 5
//            [0,1,2,3,4,5,6,7],
//            [0,1,2,3,4,5,6,7],
//            [0,1,2,3,4,5,6,7],
//            [0,1,2,3,4,5,6,7],
//            [0,1,2,3,4,5,6,7]   row 0
//        ]

        /** Down-Right
         * if cord [0,3] topLeft = [0, 3]
         * if cord [1,3] topLeft = [0, 2]
         * if cord [2,3] topLeft = [0, 1]
         * if cord [3,3] topLeft = [0, 0]
         * if cord [4,3] topLeft = [1, 0]
         * if cord [5,3] topLeft = [2, 0]
         * ------------------------------
         * Down-Left
         * if cord [0,3] topLeft = [0, 3]
         * if cord [1,3] topLeft = [0, 4]
         * if cord [2,3] topLeft = [0, 5]
         * if cord [3,3] topLeft = [0, 6]
         * if cord [4,3] topLeft = [0, 7]
         * if cord [5,3] topLeft = [1, 7]
         */

        if ( $winner ) {
            return $playerID;
        }

        return false;
    }

    private function skipCheck(array $rowColCords, bool $checkLeft = true): bool
    {
        $direction = $checkLeft ? 'down-left' : 'down-right';
        $deadCords = [
            'down-right' => [
                // row => [cols]
                0 => [5,6,7],
                1 => [6,7],
                2 => [7],
                3 => [0],
                4 => [0,1],
                5 => [0,1,2]
            ],
            'down-left' => [
                0 => [0],
                1 => [0,1],
                2 => [0,1,2],
                3 => [5,6,7],
                4 => [6,7],
                5 => [7],
            ]
        ];

        return in_array($rowColCords[1], $deadCords[$direction][$rowColCords[0]]);
    }

    private function getTopLeftCord($currentRow, $currentColumn): array
    {
        if (($currentRow === 0)) {
            return [$currentRow, $currentColumn];
        } else {
            return ($currentRow > $currentColumn) ? [($currentRow % $currentColumn), 0] : [0, ($currentRow % $currentColumn)];
        }
    }

    private function getTopRightCord($currentRow, $currentColumn): array
    {
        if (($currentRow === 0)) {
            return [$currentRow, $currentColumn];
        } else {
            return ($currentRow + $currentColumn > 7) ? [($currentRow + $currentColumn % 7), 7] : [0, ($currentRow + $currentColumn)];
        }
    }
}