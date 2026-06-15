<?php

namespace MediumDubb\ConnectFour\Domain;

class Turn
{
    public function __construct(
        public readonly string $id,
        public readonly string $board_id,
        public readonly string $player_id,
        public readonly int $turn_count
    ) {}
}