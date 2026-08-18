<?php

namespace MediumDubb\ConnectFour\Services;

use MediumDubb\ConnectFour\Domains\Board;
use MediumDubb\ConnectFour\Domains\Token;
use MediumDubb\ConnectFour\DTO\BoardResponse;
use MediumDubb\ConnectFour\Exceptions\ApiException;
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

    private function getWinner(): bool|int
    {
        $rows = 6;
        $cols = 7;

        return false;
    }
}