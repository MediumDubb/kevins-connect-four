<?php

namespace MediumDubb\ConnectFour\Controllers\Core;

use MediumDubb\ConnectFour\Core\ApiError;
use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Services\SessionService;

class CoreController
{
    private const string UID_KEY = 'UID';
    public ApiError $errors;

    public function __construct(private readonly SessionService $session, public readonly PDOConnector $db){
        $this->errors = ApiError::get();
    }

    public function getUid(): ?string
    {
        return $this->session->get(self::UID_KEY);
    }

    public function setUid(string $id): SessionService
    {
        return $this->session->set(self::UID_KEY, $id);
    }

    public function clearUid(): void
    {
        $this->session->clear();
    }
}