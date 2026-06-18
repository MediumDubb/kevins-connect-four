<?php

use Dotenv\Dotenv;
use MediumDubb\ConnectFour\AppRouter;
use MediumDubb\ConnectFour\Controllers\API\GameApiController;
use MediumDubb\ConnectFour\Controllers\LobbyController;
use MediumDubb\ConnectFour\Controllers\RoomController;

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
$router->set('/', [RoomController::class, 'index']);
$router->set('/api/create-room', [GameApiController::class, 'createRoom']);
$router->set('/api/join-room', [GameApiController::class, 'joinRoom']);
$router->set('/api/getBoardSate', [GameApiController::class, 'getBoardSate']);
$router->set('/api/dropToken', [GameApiController::class, 'dropToken']);

// Run the router using the server request data
$router->resolve($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);