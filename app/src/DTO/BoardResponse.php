<?php

namespace MediumDubb\ConnectFour\DTO;

use MediumDubb\ConnectFour\Domains\Board;
use MediumDubb\ConnectFour\Domains\Token;
use MediumDubb\ConnectFour\Exceptions\ApiException;

class BoardResponse
{
    public function __construct(
        public int $id,
        public PlayerResponse $player1,
        public ?PlayerResponse $player2,
        public ?int $current_player,
        public ?int $winner,
        public array $tokens,
    ) {}

    /**
     * @throws ApiException
     */
    public static function fromDomain(Board $board): self
    {
        return new self(
            id: $board->getBoardID(),
            player1: PlayerResponse::fromDomain($board->getPlayer1()),
            player2: PlayerResponse::fromDomain($board->getPlayer2()),
            current_player: $board->getCurrentPlayer(),
            winner: $board->getWinner(),
            tokens: array_map(
                fn (Token $token) => TokenResponse::fromDomain($token),
                $board->getTokens()
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'player1'  => $this->player1->toArray(),
            'player2' => $this->player2->toArray(),
            'current_player' => $this->current_player,
            'winner' => $this->winner,
            'tokens' => array_map(
                fn (TokenResponse $token) => $token->toArray(),
                $this->tokens
            ),
        ];
    }
}