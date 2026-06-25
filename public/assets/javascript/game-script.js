const base_uri = window.location.protocol + "//" + window.location.hostname + "/api";
const room_id = getRoomID();
let polling, poll_interval_id;
let board_state = {
    player_class: null,
    room_ready: false,
    setting_token: false,
    my_turn: false,
    finished: false,
    tokens: [],
};

document.addEventListener("DOMContentLoaded", (e) => {
    // heart_beat();
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

// on board hover check if player is the current player
// disable any action if the server returns false
// else allow them to interact



function eventManager()
{
    let cols = document.querySelectorAll('#gameBoard form .board div.column')
    cols.forEach(col => {
        col.addEventListener('click', (e) => {
            let currCol = e.currentTarget
            console.log(currCol)
        })
    })
}

function setBoardState(resObj) {
    if (resObj.data) {
        board_state.player_class = resObj.data.player_class
        board_state.room_ready = resObj.data.room_ready
        board_state.setting_token = resObj.data.setting_token
        board_state.my_turn = resObj.data.my_turn
        board_state.tokens = resObj.data.tokens
        board_state.finished = resObj.data.finished
        renderBoard();
    } else {
        board_state.errors = resObj.data.error;
        displayerErrors();
    }
}

function setToken(column)
{
    const endpoint = base_uri + '/drop-token';
    const formBody = new URLSearchParams({
        room_id: room_id,
        board_column: column
    });

    try {
        const response = fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formBody
        });
        const data = JSON.parse(response.text());
        setBoardState(data.result);

        if (!board_state.my_turn && !board_state.finished) {
            startPolling();
        }
    } catch (err) {
        console.log('Token post failed: ' + err);
    }

}

function displayerErrors()
{

}

function getRoomID()
{
    return window.location.pathname.split("/").pop();
}

function startPolling()
{
    if (polling) {
        return;
    }

    polling = true;
    pollBoard();
}

function pollBoard()
{
    if (!polling) {
        return;
    }

    try {
        const response = fetch(base_uri + '/get-state?room_id=' + room_id);
        const data = JSON.parse(response.text());
        setBoardState(data.result);
        if (board_state.my_turn || board_state.finished) {
            polling = false;
            clearInterval(poll_interval_id);
            return;
        }
    } catch (error) {
        console.error('Polling failed:', error);
    }

    poll_interval_id = setInterval(pollBoard, 3000);
}

function renderBoard()
{

}