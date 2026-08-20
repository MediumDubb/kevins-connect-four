<?php

namespace MediumDubb\ConnectFour\Domains;

use MediumDubb\ConnectFour\Exceptions\ApiException;
use MediumDubb\ConnectFour\Repositories\PlayerRepo;

final readonly class Player
{
    public function __construct(private ?int $id = null){}

    public static function fromDB(array $row): self {
        return new self(
            id: $row['id'],
        );
    }

    /**
     * @throws ApiException
     */
    public static function getByID(int $id): self {
        $playerRow = new PlayerRepo()->getPlayerByID($id);

        return new self(
            id: $playerRow['id'],
        );
    }

    /**
     * @throws ApiException
     */
    public static function create(): self {
        $id = new PlayerRepo()->getNewPlayerID();

        return new self(
            id: $id,
        );
    }

    public function getPlayerID(): int
    {
        return $this->id;
    }
}