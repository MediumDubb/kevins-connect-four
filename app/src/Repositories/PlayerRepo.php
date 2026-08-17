<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Domains\Player;
use MediumDubb\ConnectFour\Exceptions\ApiException;
use PDO;

class PlayerRepo
{
    private PDOConnector $db;
    private const string TABLE_NAME = 'players';

    private const array DB_COLUMNS = [
        'id',
        'player_name',
    ];

    public function __construct() {
        $this->db = new PDOConnector();
    }

    public function getNewPlayerID(): int
    {
        $this->db->run(
            "INSERT INTO ". self::TABLE_NAME ." () VALUES ()"
        );

        return $this->db->pdo->lastInsertId();
    }

    /**
     * @throws ApiException
     */
    public function getPlayerByID(string $playerID): Player
    {
        $stmt = $this->db->run(
            "SELECT * FROM ". self::TABLE_NAME ." WHERE id = :id",
            [
                ':id' => $playerID
            ]
        );

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return Player::fromDB($row);
    }
}