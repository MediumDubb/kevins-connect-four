<?php

namespace MediumDubb\ConnectFour\Domains;

use MediumDubb\ConnectFour\Exceptions\ApiException;
use MediumDubb\ConnectFour\Repositories\TokenRepo;

final readonly class Token
{
    public function __construct(
        private int $board,
        private int $player,
        private int $board_column,
        private ?int $board_row = null
    ){}

    public static function fromDB(array $row): self
    {
        return new self(
            board: $row['board'],
            player: $row['player'],
            board_column: $row['board_column'],
            board_row: $row['board_row']
        );
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

    /**
     * @throws ApiException
     */
    public function getBoardRow(): int
    {
        return is_int($this->board_row) ? $this->board_row : new TokenRepo()->getColRowCount($this->board, $this->board_column);
    }

    public function getTokenCords(): array
    {
        return [$this->board_row, $this->board_column];
    }

}