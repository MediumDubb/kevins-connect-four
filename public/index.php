<?php

use Dotenv\Dotenv;
use MediumDubb\ConnectFour\Controllers\API\GameApiController;
use MediumDubb\ConnectFour\Controllers\RoomController;
use MediumDubb\ConnectFour\Core\AppRouter;

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

$router = AppRouter::getRouter();

// Define your routes here
$router->get('/', [new RoomController(), 'index']);
$router->get('/room/{id}', [new RoomController(), 'gameRoom']);
$router->get('/api/join-room', [GameApiController::class, 'joinRoom']);
$router->get('/api/getBoardSate', [GameApiController::class, 'getBoardSate']);
$router->get('/api/dropToken', [GameApiController::class, 'dropToken']);
$router->post('/api/create-room', [GameApiController::class, 'createRoom']);

// Run the router using the server request data
$router->dispatch();