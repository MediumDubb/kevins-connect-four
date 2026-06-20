<?php

namespace MediumDubb\ConnectFour\Controllers\API;

class GameApiController
{

    private function createRoom()
    {
        // accepts empty POST
        // check for valid user ID in session
            // if none exist,
                // then create one
                // store char id in session
        // generate new board row
        // get board ID string

        // append to URL and redirect user to board
        // OR
        // save game state object in JS that's updated from server @ start of every turn
    }

    private function joinRoom()
    {
        // accepts GET with board ID
        // check for valid user ID in session
            // if none exist,
                // then create one
                // store char id in session

        // take ID provided and
            // append to URL and redirect user to board
            // OR
            // save game state object in JS that's updated from server @ start of every turn
    }

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