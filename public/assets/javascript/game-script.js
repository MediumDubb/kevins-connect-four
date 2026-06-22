let board_state = {
    'room_id': null,
    'user_id': null,
    'color': null,
    'current_player': null,
    'tokens': [],
    'finished': false,
    'winner_id': null,
}

document.addEventListener("DOMContentLoaded", (e) => {

});

// expected user flow:
// page user is validated and loads onto page once it has been confirmed their session contains a player id
// game state is immediately pulled and the board_state is updated
// heart beat is set for every 5 seconds while waiting for second player to be added to room/board table
// once player two has made a connection the game can start
// user clicks must be confirmed by the server before token placement is accepted and animation can occur.
    // the current user will be notified when it is their turn
    // any user who spams server with click while it's not their turn will be penalized
// token placement will trigger a validation sequence that will watch for the win conditions
// once a user wins or a stalemate has been achieved, a play again action button will appear.

// possible one-off events that must be controlled:



function gameInit()
{

}

function alertUser()
{

}