<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Domain\Player;
use MediumDubb\ConnectFour\Database\PDOConnector;

class PlayerRepo
{
    private const string TABLE = 'players';

    private const array ALLOWED_CREATE_FIELDS = [
        'name',
        'level',
        'score',
        'team_id',
    ];

    private const array ALLOWED_UPDATE_FIELDS = [
        'name',
        'level',
        'score',
        'team_id',
    ];

    public function __construct(private readonly PDOConnector $db)
    {}

    public function findByID(string $id): ?Player
    {

    }

    public function create(array $data): string
    {

    }

    public function updateByID(string $id, array $data): bool
    {

    }

    public function deleteByID(string $id): bool
    {

    }

    public function mapToModel(array $rowData): Player
    {

    }
}