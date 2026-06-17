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

    public function getUUID(): string
    {
        $sql ="SELECT BIN_TO_UUID(id, 1) AS id, created_at FROM boards;";
    }
}