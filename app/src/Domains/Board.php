<?php

namespace MediumDubb\ConnectFour\Domains;

use MediumDubb\ConnectFour\Exceptions\ApiException;
use MediumDubb\ConnectFour\Repositories\PlayerRepo;
use MediumDubb\ConnectFour\Repositories\TokenRepo;

final readonly class Board
{
    public function __construct(
        private int  $id,
        private int  $player1,
        private ?int $player2 = null,
        private ?int $current_player = null,
        private ?int $winner = null
    ){}

    public static function fromDB(array $row): self {
        return new self(
            id: $row['id'],
            player1: $row['player1'],
            player2: $row['player2'],
            current_player: $row['current_player'],
            winner: $row['winner'],
        );
    }

    /**
     * @throws ApiException
     */
    public function getTokens(): array {
        return $this->id ? new TokenRepo()->getTokensByBoardID($this->id) : [];
    }

    public function getPlayer1(): ?Player {
        return $this->player1 ? new PlayerRepo()->getPlayerByID($this->player1) : null;
    }

    public function getPlayer2(): ?Player {
        return $this->player2 ? new PlayerRepo()->getPlayerByID($this->player2) : null;
    }

    public function getCurrentPlayer(): ?int {
        return $this->current_player;
    }

    public function getWinner(): ?int {
        return $this->winner;
    }

    public function getBoardID(): int
    {
        return $this->id;
    }

    public function boardIsFull(): bool
    {
        return $this->player2 === null;
    }
}