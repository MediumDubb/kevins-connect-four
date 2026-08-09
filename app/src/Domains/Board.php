<?php

namespace MediumDubb\ConnectFour\Domains;

class Board
{
    public function __construct(
        public readonly string $id,
        public readonly string $player_one_id,
        public readonly string $player_two_id,
        public readonly string $current_player_id,
        public readonly Token $tokens,
        public readonly ?string $winner_id,
        public readonly bool $finished,
    ){}
}