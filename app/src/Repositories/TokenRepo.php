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

    public function createToken(array $data): void
    {
        $this->db->run(
            "INSERT INTO tokens (id, board_id, player_id, board_row, board_column) 
                    (
                        id,
                        board_id,
                        player_id,
                        board_row,
                        board_column
                     ) 
                    VALUES 
                    (
                         (UUID_TO_BIN(UUID(), 1)),
                         :board_id,
                         :player_id,
                         :board_row,
                         :board_column,
                     )",
            [
                'board_id' => $data['board_id'],
                'player_id' => $data['player_id'],
                'board_row' => $data['board_row'],
                'board_column' => $data['board_column'],
            ]
        );
    }

    public function getByBoardID(int $board_id): array
    {

    }

    public function mapToModel(array $rowData): Token
    {

    }
}