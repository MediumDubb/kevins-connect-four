<?php

namespace MediumDubb\ConnectFour\Repositories;

use Exception;
use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Domains\Board;
use MediumDubb\ConnectFour\Exceptions\ApiException;
use PDO;
use PDOException;

class BoardRepo
{
    private PDOConnector $db;
    private const string TABLE = 'boards';

    private const array DB_COLUMNS = [
        'id',
        'player_one_id',
        'player_two_id',
        'current_player_id',
        'winner_id',
        'finished',
    ];

    public function __construct() {
        $this->db = new PDOConnector();
    }

    /**
     * @throws ApiException
     */
    public function getBoardByID(string $boardID): Board
    {
        try {
            $stmt = $this->db->run(
                "SELECT * FROM " . self::TABLE . " WHERE id = :boardID LIMIT 1",
                [
                    ':boardID' => $boardID
                ]
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return Board::fromDB($row);
        } catch (PDOException $e) {
            throw new ApiException('PDOServerSideError', 'Internal server error: lookup failed', 500);
        }
    }

    /**
     * @throws ApiException
     */
    public function create(string $player_id): Board
    {
        if (filter_var($player_id, FILTER_VALIDATE_INT)) {
            $player_id = intval($player_id);
        } else {
            throw new ApiException('InvalidPlayerID', 'Invalid player ID');
        }

        try {
            $this->db->run(
                "INSERT INTO " . self::TABLE . " (player1) VALUES (:player_one_id)",
                [
                    'player_one_id' => $player_id,
                ]
            );

            $boardID = $this->db->pdo->lastInsertId();
        } catch (PDOException|Exception $e) {
            throw new ApiException('PDOServerSideError', 'Internal server error: unable to create', 500);
        }

        return $this->getBoardByID($boardID);
    }

    /**
     * @throws ApiException
     */
    public function join(string $board_id, string $player_two_id): Board
    {
        try {
            $this->db->run(
                "UPDATE " . self::TABLE . "
                    SET (player_two_id, current_player) = (:player_two_id, :current_player_id)
                    WHERE id = :id",
                [
                    'player_two_id' => $player_two_id,
                    'current_player_id' => $player_two_id
                ]
            );
        } catch (PDOException $e) {
            throw new ApiException('PDOServerSideError', 'Internal server error: unable to join.', 500);
        }

        return $this->getBoardByID($board_id);
    }

    /**
     * @throws ApiException
     */
    public function getOpenBoardID(): ?string
    {
        try {
            $stmt = $this->db->run(
                "SELECT id FROM " . self::TABLE . " WHERE player_two_id IS NULL LIMIT 1"
            );

            $id = $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new ApiException('PDOServerSideError', 'Internal server error: failed on open board lookup', 500);
        }

        return $id;
    }

    /**
     * @throws ApiException
     */
    public function setBoardWinner(string $winnerID, string $boardID): void
    {
        try {
            $this->db->run(
                "UPDATE " . self::TABLE . "
                    SET winner_id = :winner_id,
                        board_finished = 1
                    WHERE id = :id
                    AND board_finished = 0",
                [
                    'winner_id' => $winnerID,
                    'id' => $boardID
                ]
            );
        } catch (PDOException $e) {
            throw new ApiException('PDOServerSideError', 'Internal server error: failed persisting winner', 500);
        }
    }

    /**
     * @throws ApiException
     */
    public function getUpdatedTurnByBoardID(string $boardID): Board
    {
        try {
            $stmt = $this->db->run(
                "SELECT 
                    CASE 
                        WHEN current_player = player1 THEN player2
                        ELSE player1
                    END AS opposing_player_id
                FROM " . self::TABLE . "
                WHERE id = :boardID",
                [
                    'boardID' => $boardID
                ]
            );

            $nextPlayerID = $stmt->fetchColumn() ?? null;
        } catch (PDOException $e) {
            throw new ApiException('PDOServerSideError', 'Internal server error: failed on turn assignment', 500);
        }

        return $this->updatePlayerTurn( $boardID,  $nextPlayerID);
    }

    /**
     * @throws ApiException
     */
    private function updatePlayerTurn(string $boardID, string $playerID): Board
    {
        try {
            $this->db->run(
                "UPDATE " . self::TABLE . "
                    SET current_player_id = :current_player_id
                    WHERE id = :id
                    AND board_finished = 0",
                [
                    'current_player_id' => $playerID,
                    'id' => $boardID
                ]
            );
        } catch (PDOException $e) {
            throw new ApiException('PDOServerSideError', 'Internal server error: failed turn update', 500);
        }

        return $this->getBoardByID($boardID);
    }

    public function getRoomPlayerIDs(string $roomID): ?array
    {
        $stmt = $this->db->run(
            "SELECT 
                    player1 AS player_one_id,
                    player2 AS player_two_id
                    FROM " . self::TABLE . "
                    WHERE id = :roomID
                    AND board_finished = 0
                    LIMIT 1",
            [
                'roomID' => $roomID,
            ]
        );

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getPlayerOne(string $roomID): ?string
    {
        $stmt = $this->db->run(
            "SELECT 
                    player1 AS player_one_id
                    FROM " . self::TABLE . "
                    WHERE id = :roomID
                    LIMIT 1",
            [
                'roomID' => $roomID,
            ]
        );

        return $stmt->fetchColumn() ?: null;
    }
}