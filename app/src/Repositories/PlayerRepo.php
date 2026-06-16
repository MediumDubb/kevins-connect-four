<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Domains\Player;
use MediumDubb\ConnectFour\Database\PDOConnector;
use PDO;

class PlayerRepo
{
    private const string TABLE = 'players';

    private const array ALLOWED_CREATE_FIELDS = [
        'name',
        'color',
        'ip'
    ];

    private const array ALLOWED_UPDATE_FIELDS = [
        'name',
        'color',
        'board_id',
    ];

    public function __construct(private readonly PDOConnector $db)
    {}

    public function findByID(string $id): ?Player
    {
        $sql = 'SELECT * FROM players WHERE id = :id LIMIT 1';

        $stmt = $this->db->run($sql, ['id' => $id]);

        $player = $stmt->fetch(PDO::FETCH_ASSOC);

        return $player ?: null;
    }

    public function create(array $data): string
    {

    }

    public function updateByID(string $id, array $data): bool
    {

    }

    public function deleteByID(string $id): bool
    {

    }

    public function mapToModel(array $rowData): Player
    {

    }
}