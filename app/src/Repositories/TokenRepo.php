<?php

namespace MediumDubb\ConnectFour\Repositories;

use MediumDubb\ConnectFour\Domains\Token;
use MediumDubb\ConnectFour\Database\PDOConnector;

class TokenRepo
{
    private const string TABLE = 'turns';

    private const array ALLOWED_CREATE_FIELDS = [

    ];

    private const array ALLOWED_UPDATE_FIELDS = [

    ];

    private const array ALLOWED_PUBLIC_FIELDS = [

    ];

    public function __construct(private readonly PDOConnector $db)
    {}

    public function findByID(string $id): ?Token
    {

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

    public function mapToModel(array $rowData): Token
    {

    }
}