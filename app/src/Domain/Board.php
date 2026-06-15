<?php

namespace MediumDubb\ConnectFour\Domain;

class Board
{
    public function __construct(
        public readonly string $id,
        public readonly string $player_one_id,
        public readonly string $player_two_id,
        public readonly string $board_matrix_json,
        public readonly ?string $winner_id,
        public readonly bool $finished,
    ) {}
}