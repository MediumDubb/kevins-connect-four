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

    public function createPlayer(string $name): string
    {
        $stmt = $this->db->run("SELECT UUID() AS id");
        $id = $stmt->fetchColumn();

        $this->db->run(
            "INSERT INTO players (id, player_name) VALUES (UUID_TO_BIN(:id, 1), :player_name)",
            [
                'id' => $id,
                'player_name' => $name
            ]
        );

        return $id;
    }
}