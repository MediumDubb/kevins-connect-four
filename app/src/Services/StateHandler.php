<?php

namespace MediumDubb\ConnectFour\Services;

class StateHandler
{

    public static function fromGeneralBoardRequest(): self {
        return new self();
    }

    public static function fromTokenDropRequest(): self {
        return new self();
    }
}