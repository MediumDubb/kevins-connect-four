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

        return $this->mapToModel( $stmt->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * @param $id
     * @return Player
     *
     *  ToDo - Need to figure out how validate and map all the data to the Player model
     */

    private function mapToPlayer($id): Player
    {
        return new Player($id);
    }
}