<?php

namespace MediumDubb\ConnectFour\Domains;

final readonly class Token
{

    public function __construct(
        private int $id,
        private int $board,
        private int $player,
        private int $board_column
    ){}

    public function fromDB(array $row): self
    {
        return new self(
            id: $row['id'],
            board: $row['board'],
            player: $row['player'],
            board_column: $row['board_column']
        );
    }

    public function getTokenID(): int
    {
        return $this->id;
    }

    public function getBoardID(): int
    {
        return $this->board;
    }

    public function getPlayerID(): int
    {
        return $this->player;
    }

    public function getBoardColumn(): int
    {
        return $this->board_column;
    }
}