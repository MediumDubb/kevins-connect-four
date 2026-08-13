<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Domains\Token;
use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Exceptions\ApiException;
use PDO;
use PDOException;

class TokenRepo
{
    private const string TABLE = 'tokens';

    private const array DB_COLUMNS = [
        'id',
        'board_id',
        'player_id',
        'board_column',
    ];

    public function __construct(private readonly PDOConnector $db)
    {}

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
                    'board_id' => $this->getBIN($data['room_id']),
                    'player_id' => $this->getBIN($data['player_id']),
                    'board_column' => intval($data['board_column']),
                ]
            );
        } catch (PDOException $e) {
            throw new ApiException('Failed to set token', 500);
        }
    }

    public function getByBoardID(int $board_id): ?array
    {
        $board_bin = $this->getBIN($board_id);

        $stmt = $this->db->run(
            "SELECT 
                    BIN_TO_UUID(id, 1) AS id,
                    BIN_TO_UUID(board_id, 1) AS board_id,
                    BIN_TO_UUID(player_id, 1) AS player_id,
                    board_column
                    FROM boards
                    WHERE id = :board_bin",
            [
                'board_bin' => $board_bin,
            ]
        );

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function mapToModel(array $rowData): Token
    {

    }

    private function getBIN(string $id)
    {
        $stmt = $this->db->run(
            "SELECT UUID_TO_BIN(:id, 1) AS id",
            ['id' => $id]
        );

        return $stmt->fetchColumn();
    }
}