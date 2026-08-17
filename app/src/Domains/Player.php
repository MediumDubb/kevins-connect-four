<?php

namespace MediumDubb\ConnectFour\Domains;

use MediumDubb\ConnectFour\Exceptions\ApiException;

final readonly class Player
{
    public function __construct(private int $id){}

    public function fromDB(array $row): self {
        return new self(
            id: $row['id'],
        );
    }

    public function getPlayerID(): int
    {
        return $this->id;
    }
}