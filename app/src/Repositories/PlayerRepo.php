<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Database\PDOConnector;

class PlayerRepo
{
    private const string TABLE = 'players';

    private const array DB_COLUMNS = [
        'id',
        'player_name',
    ];

    public function __construct(private readonly PDOConnector $db){}

    public function getNewPlayerID(): string
    {
        $this->db->run(
            "INSERT INTO players (id) VALUES (NULL)"
        );

        return $this->db->pdo->lastInsertId();
    }

    public function getPlayerByID(string $playerID): string
    {
        $stmt = $this->db->run(
            "SELECT id FROM players WHERE id = :id",
            [
                ':id' => $playerID
            ]
        );

        return $stmt->fetchColumn();
    }
}