<?php

namespace MediumDubb\ConnectFour\Domains;

class Token
{
    public function __construct(
        public readonly string $id,
        public readonly string $board_id,
        public readonly string $player_id,
        public readonly int $column,
        public readonly int $row
    ) {}
}