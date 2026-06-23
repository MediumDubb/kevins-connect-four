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
            "INSERT INTO boards (id, player_one_id) VALUES (UUID_TO_BIN(:id, 1), UUID_TO_BIN(:player_one_id, 1))",
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
                    SET player_two_id = UUID_TO_BIN(:player_two_id), current_player_id = UUID_TO_BIN(:current_player_id)
                    WHERE id = :id",
            [
                'player_two_id' => $player_two_id,
                'current_player_id' => $player_two_id,
                'id' => $bin
            ]
        );
    }
    
    public function getOpenBoardID(): ?string
    {
        $stmt = $this->db->run(
            "SELECT BIN_TO_UUID(id, 1) FROM boards WHERE player_two_id IS NULL LIMIT 1"
        );

        $id = $stmt->fetchColumn();

        return $id ?: null;
    }

    public function updateTurn(string $player_id, array $data): void
    {
        $player_bin = $this->getBIN($player_id);

        $this->db->run(
            "UPDATE boards
                    SET current_player_id = UUID_TO_BIN(:current_player_id)
                    WHERE player_one_id = UUID_TO_BIN(:p1_id)
                    OR player_two_id = UUID_TO_BIN(:p2_id)
                    AND finished = 0",
            [
                'current_player_id' => $data['other_player_id'],
                'p1_id' => $player_bin,
                'p2_id' => $player_bin
            ]
        );
    }

    public function getRoomPlayerIDs(string $room_id): ?array
    {
        $room_bin = $this->getBIN($room_id);

        $stmt = $this->db->run(
            "SELECT 
                        BIN_TO_UUID(player_one_id, 1) AS player_one_id,
                        BIN_TO_UUID(player_two_id, 1) AS player_two_id
                    FROM boards
                    WHERE id = :room_bin
                    AND board_finished = 0
                    LIMIT 1",
            [
                'room_bin' => $room_bin,
            ]
        );

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getBoardState(string $room_id): ?array
    {
        $room_bin = $this->getBIN($room_id);

        $stmt = $this->db->run(
            "SELECT 
                    BIN_TO_UUID(id, 1) AS id,
                    BIN_TO_UUID(player_one_id, 1) AS player_one_id,
                    BIN_TO_UUID(player_two_id, 1) AS player_two_id,
                    BIN_TO_UUID(current_player_id, 1) AS current_player_id,
                    BIN_TO_UUID(winner_id, 1) AS winner_id,
                    board_finished
                    FROM boards
                    WHERE id = :room_bin
                    LIMIT 1",
            [
                'room_bin' => $room_bin,
            ]
        );

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getBoardTokens(string $room_id): array
    {
        $room_bin = $this->getBIN($room_id);

        $stmt = $this->db->run(
            "SELECT 
                    *
                    FROM tokens
                    WHERE board_id = :room_bin",
            [
                'room_bin' => $room_bin,
            ]
        );

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function mapToModel(array $rowData): Board
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