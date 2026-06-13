<?php

namespace MediumDubb\ConnectFour\Controllers;

use MediumDubb\ConnectFour\Services\PDOConnector;

class EntryController
{
    private ?PDOConnector $db = null;

    public function __construct()
    {
        $this->db = new PDOConnector();
    }

    public function index(): void
    {
        require_once (__DIR__ . "../../Views/Home.php");
    }
}