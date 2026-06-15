<?php

namespace MediumDubb\ConnectFour\Controllers;

class LobbyController
{
    public function index(): void
    {
        require_once (dirname(__DIR__) . "/Views/Lobby.php");
    }
}