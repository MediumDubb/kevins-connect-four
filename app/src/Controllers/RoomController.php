<?php

namespace MediumDubb\ConnectFour\Controllers;

class RoomController
{
    public function index(): void
    {
        require_once (dirname(__DIR__) . "/Views/Room.php");
    }
}