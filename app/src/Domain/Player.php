<?php

namespace MediumDubb\ConnectFour\Domain;

class Player
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $color,
        public readonly string $ip
    ) {}
}