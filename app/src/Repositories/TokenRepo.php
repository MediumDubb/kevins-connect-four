<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Domains\Token;
use MediumDubb\ConnectFour\Database\PDOConnector;

class TokenRepo
{
    private const string TABLE = 'tokens';

    private const array DB_COLUMNS = [
        'id',
        'board_id',
        'player_id',
        'board_row',
        'board_column',
    ];

    public function __construct(private readonly PDOConnector $db)
    {}

    public function createToken(array $data): string
    {

    }

    public function mapToModel(array $rowData): Token
    {

    }
}