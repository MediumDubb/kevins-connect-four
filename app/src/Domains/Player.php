<?php

namespace MediumDubb\ConnectFour\Domains;

class Player
{
    public function __construct(
        public readonly string $id,
        public readonly string $player_name
    ) {}
}