<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Domains\Token;
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

    /**
     * @throws ApiException
     */
    public function getTokensByBoardID(int $boardID): ?array
    {
        $stmt = $this->db->run(
            "SELECT 
                    board,
                    player,
                    board_column
                    FROM " . self::TABLE ."
                    WHERE board = :boardID",
            [
                'boardID' => $boardID,
            ]
        );

        return $this->mapToModel($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @throws ApiException
     */
    private function mapToModel(array $rowData, bool $single = false)
    {
        $tokens = [];

        foreach ($rowData as $row) {
            $tokens[] = new Token($row);
        }

        return $single ? $tokens[0] : $tokens;
    }
}