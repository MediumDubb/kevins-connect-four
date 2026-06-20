<?php

namespace MediumDubb\ConnectFour\Controllers\API;

use MediumDubb\ConnectFour\Controllers\Core\CoreController;

class GameApiController extends CoreController
{
    public function dropToken()
    {
        // check if player turn
        // check if current board is full
            // if no to either
                // return error and a timeout
        // if proper player and board not full
            // create token
            // consume request to fill token row
    }

    // called after every action
    public function getBoardSate()
    {
        // fetch board state
        // serialize to api spec
        // respond to frontend
    }
}