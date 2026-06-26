let polling, poll_interval_id, cols, board_state, abort_controller;
board_state = {
    player_class: null,
    room_ready: false,
    setting_token: false,
    my_turn: false,
    board_finished: false,
    tokens: [],
};
cols = document.querySelectorAll('#gameBoard form .board > div.column');
poll_interval_id = null;
const base_uri = window.location.protocol + "//" + window.location.hostname + "/api";
const room_id = getRoomID();
const setColsClickEvent = (e) => {
    setToken(parseInt(e.currentTarget.dataset.col));
}

// entry/init
document.addEventListener("DOMContentLoaded", () => {
    startPolling(); // infinite compounding loop by not checking if an interval existed
});

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

async function pollBoard()
{
    if (!polling) {
        return;
    }

    try {
        const response = await fetch(base_uri + '/get-state?room_id=' + room_id);
        const data = JSON.parse(await response.text());
        console.log(data);
        setBoardState(data.result);
        if (board_state.my_turn || board_state.board_finished) {
            polling = false;
            clearInterval(poll_interval_id);
            poll_interval_id = null;
            setBoardEvents();
            return;
        }
    } catch (error) {
        console.error('Polling failed:', error);
    }

    if (poll_interval_id === null) {
        poll_interval_id = setInterval(pollBoard, 3000);
    }
}

function setBoardState(resObj) {
    if (resObj.data) {
        board_state.player_class = resObj.data.player_class
        board_state.room_ready = resObj.data.room_ready
        board_state.setting_token = resObj.data.setting_token
        board_state.my_turn = resObj.data.my_turn
        board_state.tokens = resObj.data.tokens
        board_state.board_finished = resObj.data.board_finished
        renderBoard();
    } else {
        board_state.errors = resObj.data.error;
        displayerErrors();
    }
}

async function setToken(column)
{
    const endpoint = base_uri + '/drop-token';
    const formBody = new URLSearchParams({
        room_id: room_id,
        board_column: column
    });

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formBody
        });
        const data = JSON.parse(await response.text());
        setBoardState(data.result);

        if (!board_state.my_turn && !board_state.board_finished) {
            startPolling();
            abortBoardEvents();
        }
    } catch (err) {
        console.log('Token post failed: ' + err);
    }

}

function setBoardEvents()
{
    if (abort_controller) {
        abort_controller.abort();
    }

    abort_controller = new AbortController();

    cols.forEach(col => {
        col.addEventListener('click', setColsClickEvent, { signal: abort_controller.signal })
    });
}

function abortBoardEvents()
{
    if (abort_controller) {
        abort_controller.abort();
    }
}

function renderBoard()
{

}

function displayerErrors()
{
    return "Errors";
}