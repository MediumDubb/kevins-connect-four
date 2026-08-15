<?php

namespace MediumDubb\ConnectFour\Domains;

use MediumDubb\ConnectFour\Exceptions\ApiException;
use MediumDubb\ConnectFour\Repositories\TokenRepo;

class Board
{
    private int $id;
    private Player $player1;
    private ?Player $player2 = null;
    private ?int $current_player = null;
    private ?int $winner = null;
    private array $tokens;

    /**
     * @throws ApiException
     */
    public function __construct(array $boardData){
        foreach ($boardData as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            } else {
                throw new ApiException('InvalidBoardProperty', 'Invalid board property: ' . $key);
            }
        }

        $this->tokens = property_exists($this, 'id') ? new TokenRepo()->getTokensByBoardID($this->id) : [];
    }

    public function getTokens(): array {
        return $this->tokens;
    }

    public function getPlayer1(): Player {
        return $this->player1;
    }

    public function getPlayer2(): ?Player {
        return $this->player2;
    }

    public function getCurrentPlayer(): ?int {
        return $this->current_player;
    }

    public function getWinner(): ?int {
        return $this->winner;
    }

    public function getBoardID(): int
    {
        return $this->id;
    }

    public function getBoardState(): array
    {
        return [
            'id' => $this->id,
            'player1' => $this->player1,
            'player2' => $this->player2,
            'current_player' => $this->current_player,
            'winner' => $this->winner,
            'tokens' => $this->tokens,
        ];
    }


}