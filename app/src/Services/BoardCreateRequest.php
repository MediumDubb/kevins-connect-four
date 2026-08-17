<?php

namespace MediumDubb\ConnectFour\Services;

use MediumDubb\ConnectFour\Domains\Board;
use MediumDubb\ConnectFour\Exceptions\ApiException;
use MediumDubb\ConnectFour\Repositories\BoardRepo;

final readonly class BoardCreateRequest
{
    public function __construct(
        private int $playerId,
    ){}

    /**
     * @throws ApiException
     */
    public static function validateRequestMethod(int|string $playerId): self
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new ApiException("InvalidRequest", "Invalid request method", 405);
        }
        if (
            $playerId === '' ||
            $playerId === '0' ||
            !filter_var($playerId, FILTER_VALIDATE_INT)
        ) {
            throw new ApiException("InvalidRequest", "Invalid creation request", 400);
        }

        return new self(
            playerId: $playerId
        );
    }

    /**
     * @throws ApiException
     */
    public function getNewBoard(): Board
    {
        return new BoardRepo()->create($this->playerId);
    }
}