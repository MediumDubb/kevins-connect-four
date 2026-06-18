<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Domains\Board;
use MediumDubb\ConnectFour\Database\PDOConnector;
use PDO;

class BoardRepo
{
    private const string TABLE = 'boards';

    private const array DB_COLUMNS = [
        'id',
        'player_one_id',
        'player_two_id',
        'current_player_id',
        'winner_id',
        'finished',
    ];

    public function __construct(private readonly PDOConnector $db){}

    public function createBoard(string $player_id): string
    {
        $stmt = $this->db->run("SELECT UUID() AS id");
        $id = $stmt->fetchColumn();

        $this->db->run(
            "INSERT INTO boards (id, player_one_id) VALUES (UUID_TO_BIN(:id, 1), :player_one_id)",
            [
                'id' => $id,
                'player_one_id' => $player_id,
            ]
        );

        return $id;
    }

    public function joinBoard(string $board_id, string $player_two_id): void
    {
        $bin = $this->getBIN($board_id);

        $this->db->run(
            "UPDATE boards
                    SET player_two_id = :player_two_id, current_player_id = :current_player_id
                    WHERE id = :id",
            [
                'player_two_id' => $player_two_id,
                'current_player_id' => $player_two_id,
                'id' => $bin
            ]
        );
    }

    public function updateTurn(string $player_id, array $data): void
    {
        $player_bin = $this->getBIN($player_id);

        $this->db->run(
            "UPDATE boards
                    SET current_player_id = :current_player_id
                    WHERE player_one_id = :id
                    OR player_two_id = :id
                    AND finished = 0",
            [
                'current_player_id' => $data['other_player_id'],
                'id' => $player_bin
            ]
        );
    }

    public function mapToModel(array $rowData): Board
    {

    }

    public function getBoardTokens(): array
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