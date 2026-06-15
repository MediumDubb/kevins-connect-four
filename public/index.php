<?php

use Dotenv\Dotenv;
use MediumDubb\ConnectFour\AppRouter;
use MediumDubb\ConnectFour\Controllers\LobbyController;
use MediumDubb\ConnectFour\Controllers\GameBoardController;
use MediumDubb\ConnectFour\Controllers\MatchmakingController;

// Find autoload.php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo "autoload.php not found";
    exit(1);
}

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$router = new AppRouter();

// Define your routes here
$router->set('/', [LobbyController::class, 'index']);
$router->set('/board', [GameBoardController::class, 'index']);
$router->set('/assign', [MatchmakingController::class, 'assignBoard']);

// Run the router using the server request data
$router->resolve($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);