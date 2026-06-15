<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Domain\Board;
use MediumDubb\ConnectFour\Database\PDOConnector;

class BoardRepo
{
    private const string TABLE = 'boards';

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

    public function findByID(string $id): ?Board
    {

    }

    public function create(array $data): string
    {

    }

    public function updateByID(string $id, array $data)
    {

    }

    public function deleteByID(string $id)
    {

    }

    public function mapToModel(array $rowData): Board
    {

    }
}