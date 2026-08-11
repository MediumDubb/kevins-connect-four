<?php

namespace MediumDubb\ConnectFour\Controllers;

class PageController extends CoreController
{
    public function index(?string $roomID = null): void
    {
        require_once (dirname(__DIR__) . "/Views/Room.php");
    }
}