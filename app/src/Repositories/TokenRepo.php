<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Exceptions\ApiException;
use PDO;
use PDOException;

class TokenRepo
{
    private PDOConnector $db;
    private const string TABLE = 'tokens';

    private const array DB_COLUMNS = [
        'id',
        'board_id',
        'player_id',
        'board_column',
    ];

    public function __construct()
    {
        $this->db = new PDOConnector();
    }

    /**
     * @throws ApiException
     */
    public function setToken(array $data): void
    {
        try {
            $this->db->run(
                "INSERT INTO tokens 
                    (
                        board_id,
                        player_id,
                        board_column
                     ) 
                    VALUES 
                    (
                         :board_id,
                         :player_id,
                         :board_column
                     )",
                [
                    'board_id' => $data['room_id'],
                    'player_id' => $data['player_id'],
                    'board_column' => $data['board_column'],
                ]
            );
        } catch (PDOException $e) {
            throw new ApiException('Failed to set token', 500);
        }
    }

    public function getTokensByBoardID(int $boardID): ?array
    {
        $stmt = $this->db->run(
            "SELECT 
                    id AS id,
                    board_id AS board_id,
                    player_id AS player_id,
                    board_column
                    FROM boards
                    WHERE id = :board_bin",
            [
                'board_bin' => $boardID,
            ]
        );

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function mapToModel(array $rowData)
    {

    }
}