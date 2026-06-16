<?php

namespace MediumDubb\ConnectFour\Domains;

class Player
{
    public function __construct(
        public readonly string $id
    ) {}
}