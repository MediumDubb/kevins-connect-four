<?php

use Dotenv\Dotenv;
use MediumDubb\ConnectFour\Controllers\Game\GameApiController;
use MediumDubb\ConnectFour\Controllers\Views\RoomController;
use MediumDubb\ConnectFour\Core\AppRouter;
use MediumDubb\ConnectFour\Database\PDOConnector;
use MediumDubb\ConnectFour\Services\SessionService;

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
$session = new SessionService();
$db = new PDOConnector();

// Define your routes here
$router->get('/', [new RoomController($session, $db), 'index']);
$router->get('/room/{id}', [new RoomController($session, $db), 'gameRoom']);
$router->get('/api/dropToken', [new GameApiController($session, $db), 'dropToken']);

$router->post('/room/init', [new RoomController($session, $db), 'initGame']);
$router->post('/api/create-room', [new GameApiController($session, $db), 'createRoom']);

// Run the router using the server request data
$router->dispatch();