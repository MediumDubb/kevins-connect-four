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
    public function getTokensByBoardID(int $boardID): array
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
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            return array_map(fn($row) => Token::fromDB($row), $rows);
        }

        return $rows;
    }

    /**
     * @param $id
     * @param $board
     * @param $player
     * @param $board_column
     * @return Token
     *
     *  ToDo - Need to figure out how validate and map all the data to the Token model
     */

    private function mapToToken($id, $board, $player, $board_column): Token
    {
        return new Token($id, $board, $player, $board_column);
    }
}