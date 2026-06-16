<?php

require_once __DIR__ . '/vendor/autoload.php';

use MediumDubb\ConnectFour\Database\PDOConnector;

$db = new PDOConnector();

$db->run("
    CREATE TABLE IF NOT EXISTS players (
        id VARCHAR(36) DEFAULT (UUID()) PRIMARY KEY,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");

$db->run("
    CREATE TABLE IF NOT EXISTS boards (
        id VARCHAR(36) DEFAULT (UUID()) PRIMARY KEY,
        player_one_id VARCHAR(36) NOT NULL,
        player_two_id VARCHAR(36) NOT NULL,
        current_player_id VARCHAR(36) NOT NULL,
        winner_id VARCHAR(36) NULL,
        board_finished BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        
        CONSTRAINT board_player_one 
            FOREIGN KEY (player_one_id) REFERENCES players(id),
        
        CONSTRAINT board_player_two 
            FOREIGN KEY (player_two_id) REFERENCES players(id),
        
        CONSTRAINT board_curr_player 
            FOREIGN KEY (current_player_id) REFERENCES players(id),
        
        CONSTRAINT board_winner 
            FOREIGN KEY (winner_id) REFERENCES players(id)
    )
");

$db->run("
    CREATE TABLE IF NOT EXISTS tokens (
        id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
        board_id VARCHAR(36) NOT NULL,
        player_id VARCHAR(36) NOT NULL,
        board_row INT NOT NULL,
        board_column INT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        
        CONSTRAINT token_position_unique
            UNIQUE (board_id, board_row, board_column),
        
        CONSTRAINT valid_row
            CHECK (board_row BETWEEN 0 AND 5),
        
        CONSTRAINT valid_column
            CHECK (board_column BETWEEN 0 AND 6),
        
        CONSTRAINT token_board_fk
            FOREIGN KEY (board_id) REFERENCES boards(id),

        CONSTRAINT token_player_fk
            FOREIGN KEY (player_id) REFERENCES players(id)
    )
");

echo "Database setup complete.\n";