<?php

namespace MediumDubb\ConnectFour\DTO;

use MediumDubb\ConnectFour\Domains\Token;

class TokenResponse
{
    public function __construct(
        public int $id,
        public int $board,
        public int $player,
        public int $board_column
    ) {}

    public static function fromDomain(Token $token): self
    {
        return new self(
            id: $token->getTokenID(),
            board: $token->getBoardID(),
            player: $token->getPlayerID(),
            board_column: $token->getBoardColumn(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'board'  => $this->board,
            'player' => $this->player,
            'board_column' => $this->board_column,
        ];
    }
}