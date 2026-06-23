const base_uri = window.location.protocol + "//" + window.location.hostname + "/api";
const room_id = getRoomID();
let board_state, my_turn, setting_token;
my_turn = false;
setting_token = false;
board_state = {
    user_id: null,
    current_player_id: null,
    tokens: [],
    finished: false,
    winner_id: null,
    room_ready: false,
};

document.addEventListener("DOMContentLoaded", (e) => {
    fetchBoardState();
});

// expected user flow:
// page user is validated and loads onto page once it has been confirmed their session contains a valid player id
// game state is immediately pulled and the board_state is updated
// heart beat is set for every 5 seconds while waiting for second player to be added to room/board table
// once player two has made a connection the game can start
// user clicks must be confirmed by the server before token placement is accepted and animation can occur.
    // the current user will be notified when it is their turn
    // any user who spams server with click while it's not their turn will be penalized
// token placement will trigger a validation sequence that will watch for the win conditions
// once a user wins or a stalemate has been achieved, a play again action button will appear.

// possible one-off events that must be controlled:



function eventManager()
{

}

function fetchBoardState()
{
    const endpoint = base_uri + "/get-state?room_id=" + room_id;
    fetch(endpoint).then(async response => {
        const text = await response.text();
        return JSON.parse(text);
    })
        .then(data => {
            setBoardState(data.result);
        })
        .catch(error => {
            console.error('Fetch failed:', error);
        });
}

function setBoardState(resObj) {
    if (resObj.data) {
        // resObj.data.board_finished
        // resObj.data.current_player_id
        // resObj.data.id
        // resObj.data.player_one_id
        // resObj.data.player_two_id
        // resObj.data.winner_id

        board_state.user_id = resObj.data.id
        board_state.current_player_id = resObj.data.id
        board_state.tokens = resObj.data.tokens
        board_state.finished = resObj.data.board_finished
        board_state.winner_id = resObj.data.winner_id
        // board_state.room_ready = resObj.data.id

    }

    console.log(board_state);
}

function setPlayerSelection()
{

}

function validateSelection()
{

}

function displayerErrors()
{

}

function myTurn()
{
    return (board_state.user_id === board_state.current_player_id)
}

function getRoomID()
{
    return window.location.pathname.split("/").pop();
}