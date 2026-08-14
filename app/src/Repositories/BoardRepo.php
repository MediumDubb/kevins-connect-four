<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Exceptions\ApiException;
use PDO;
use PDOException;

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

    /**
     * @throws ApiException
     */
    public function getNewBoardBoardObj(string $player_id): array
    {
        try {
            $this->db->run(
                "INSERT INTO boards (player_one_id) VALUES (:player_one_id)",
                [
                    'player_one_id' => $player_id,
                ]
            );

            $boardID = $this->db->pdo->lastInsertId();

            $stmt = $this->db->run(
                "SELECT * FROM boards WHERE id = :id",
                [
                    'id' => $boardID,
                ]
            );

            return $stmt->fetchColumn();
        } catch (PDOException) {
            throw new ApiException('PDOServerSideError', 'The server has encountered an error', 500);
        }

    }

    public function joinBoard(string $board_id, string $player_two_id): void
    {
        $bin = $this->getBIN($board_id);

        $this->db->run(
            "UPDATE boards
                    SET player_two_id = UUID_TO_BIN(:player_two_id, 1)
                    WHERE id = :id",
            [
                'player_two_id' => $player_two_id,
                'id' => $bin
            ]
        );

        $player_ids = $this->getRoomPlayerIDs($board_id);
        if (is_array($player_ids)) {
            $index = array_rand($player_ids);
            $this->setStartPlayer($board_id, $player_ids[$index]);
        }
    }

    private function setStartPlayer(string $board_id, string $player_id): void
    {
        $bin = $this->getBIN($board_id);

        $this->db->run(
            "UPDATE boards
                    SET current_player_id = UUID_TO_BIN(:current_player_id, 1)
                    WHERE id = :id",
            [
                'current_player_id' => $player_id,
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

    /**
     * @throws ApiException
     */
    public function getBoardByID(string $boardID): array|bool
    {
        try {
            $stmt = $this->db->run(
                "SELECT * FROM boards WHERE id = :boardID LIMIT 1",
                [
                    ':boardID' => $boardID
                ]
            );

            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new ApiException('PDOServerSideError', 'A lookup error has occured', 500);
        }
    }

    public function updatePlayerTurn(string $board_id, string $player_id): void
    {
        try {
            $this->db->run(
                "UPDATE boards
                    SET current_player_id = UUID_TO_BIN(:current_player_id, 1)
                    WHERE id = UUID_TO_BIN(:id, 1)
                    AND board_finished = 0",
                [
                    'current_player_id' => $player_id,
                    'id' => $board_id
                ]
            );
        } catch (PDOException $e) {
            $test = "";
        }

    }

    public function updateWinner(string $winner_id, string $board_id): void
    {
        try {
            $this->db->run(
                "UPDATE boards
                    SET winner_id = UUID_TO_BIN(:winner_id, 1),
                        board_finished = 1
                    WHERE id = UUID_TO_BIN(:id, 1)
                    AND board_finished = 0",
                [
                    'winner_id' => $winner_id,
                    'id' => $board_id
                ]
            );
        } catch (PDOException $e) {
            $test = "";
        }

    }

    public function getAlternatePlayerID(string $board_id): ?string
    {
        $stmt = $this->db->run(
            "SELECT 
                    CASE 
                        WHEN current_player_id = player_one_id THEN BIN_TO_UUID(player_two_id, 1)
                        ELSE BIN_TO_UUID(player_one_id, 1)
                    END AS opposing_player_id
                FROM boards
                WHERE id = UUID_TO_BIN(:board_id, 1)",
            [
                'board_id' => $board_id
            ]
        );

        return $stmt->fetchColumn() ?? null;
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

    public function getPlayerOne(string $room_id): ?string
    {
        $room_bin = $this->getBIN($room_id);

        $stmt = $this->db->run(
            "SELECT 
                    BIN_TO_UUID(player_one_id, 1) AS player_one_id
                    FROM boards
                    WHERE id = :room_bin
                    LIMIT 1",
            [
                'room_bin' => $room_bin,
            ]
        );

        return $stmt->fetchColumn() ?: null;
    }

    public function getBoardState(string $room_id): ?array
    {
        $room_bin = $this->getBIN($room_id);

        try {
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
            $result =  $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $result = ['excpetion_error_message' => $e->getMessage()];
        }

        return $result;
    }

    public function getTokensByBoardID(string $room_id): array
    {
        $room_bin = $this->getBIN($room_id);

        $stmt = $this->db->run(
            "SELECT 
                    BIN_TO_UUID(board_id, 1) AS board_id,
                    BIN_TO_UUID(player_id, 1) AS player_id,
                    board_column
                    FROM tokens
                    WHERE board_id = :room_bin",
            [
                'room_bin' => $room_bin,
            ]
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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