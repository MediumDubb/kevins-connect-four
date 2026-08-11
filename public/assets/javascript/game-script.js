let polling, poll_interval_id, inputs, board_state, abort_controller;

board_state = {
    id: null,
    current_player: null,
    winner: null,
    player1: {
        id: null
    },
    player2: {
        id: null
    },
    tokens: [],
}

inputs = document.querySelectorAll('#gameBoard form .board .column input[type=radio]');

poll_interval_id = null;

const base_url = window.location.protocol + "//" + window.location.hostname + "/";
const room_id = getRoomID();

const setInputsClickEvent = (e) => {
    abortBoardEvents();
    disableBoard();
    e.currentTarget.checked = false;
    e.currentTarget.nextElementSibling.style.top = e.currentTarget.nextElementSibling.dataset.top;
    const col = parseInt(e.currentTarget.parentElement.dataset.col);
    if (! (document.querySelectorAll(`form .board .grid .column[data-col="${col}"] input[type=radio]:checked`).length >= 6) ) {
        setToken(col, e.currentTarget).then((r) => {
            if (r.error === null) {
                r.input.checked = true
                tokenSound();
            }
            r.input.nextElementSibling.style.removeProperty('top');
        });
    }
}

// =============== entry/init =================
document.addEventListener("DOMContentLoaded", () => {
    // check for existing room ID in url
    // if one exists, start polling
    if (getRoomID() !== null) {
        startPolling();
    } else {
        handleUserForm();
    }
});

// ================ helpers ===================

function getRoomID()
{
    const queryString = window.location.search;
    return new URLSearchParams(queryString).get('roomID');
}

function setRoomIDParam(roomID)
{
    const urlParams = new URLSearchParams();
    urlParams.set('roomID', roomID);
    const newRelativePath = window.location.pathname + '?' + urlParams.toString();
    window.history.pushState(null, '', newRelativePath);
}

function clearRoomIDParam()
{
    window.history.pushState(null, '', window.location.pathname);
}

function startPolling()
{
    if (polling) {
        return;
    }

    disableBoard();
    polling = true;
    pollBoard();
}

async function pollBoard()
{
    if (!polling) {
        return;
    }

    try {
        getState(room_id).then(r => handleResponse(r));
        const data = JSON.parse(await response.text());
        setBoardState(data.result);
        if (board_state.my_turn) {
            resetPolling();
            setBoardEvents();
            enableBoard();
            return;
        } else if (board_state.board_finished) {
            resetPolling();
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
        hydrateStateObj(resObj);

        document.getElementById("gameBoard").classList.add(board_state.player_class);

        document.getElementById("my_turn").innerText = board_state.my_turn && (board_state.winner_id === null) ? ' Yes' : ' No';

        renderBoard(board_state.tokens);

        if (board_state.winner_id) {
            document.getElementById("winner").innerText = board_state.player_id ===  board_state.winner_id ? ' Winner' : ' Loser';
        }
    } else {
        board_state.errors = resObj.data.error;
        displayerErrors();
    }
}

async function setToken(column, input)
{
    const endpoint = base_url + '/drop-token';
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
            disableBoard();
            startPolling();
            abortBoardEvents();
        }

        return {input: input, error: null};
    } catch (err) {
        console.log('Token post failed: ' + err);
        return {input: input, error: "Something went wrong"};
    }
}

function setBoardEvents()
{
    if (abort_controller) {
        abort_controller.abort();
    }

    if (!board_state.board_finished) { // if board is not finished assign click events
        abort_controller = new AbortController();
        inputs.forEach(radio => {
            radio.addEventListener('click', setInputsClickEvent, { signal: abort_controller.signal });
        });
    }
}

function abortBoardEvents()
{
    if (abort_controller) {
        abort_controller.abort();
    }
}

function renderBoard(tokens)
{
    const checkedInputsLength = document.querySelectorAll(`#gameBoard form .board .field.grid .column input:checked`).length;

    if ( checkedInputsLength < tokens.length ) {
        let diff = (checkedInputsLength - tokens.length);
        tokens.slice(diff).forEach((token) => {
            tokenSeeding(token);
        })
    } else if (checkedInputsLength > tokens.length) {
        document.querySelectorAll(`#gameBoard form .board .field.grid .column input:checked`).forEach((checkedInput) => {
            checkedInput.checked = false;
        })
        tokens.forEach((token) => {
            tokenSeeding(token);
        })
    }
}

function tokenSeeding(token) {
    const uncheckedColInputs= document.querySelectorAll(`#gameBoard form .board .field.grid .column[data-col="${token.board_column}"] input:not(:checked)`);
    let tokenClass = board_state.player_id === token.player_id ? board_state.player_class : null;
    if (tokenClass === null) {
        if ( board_state.player_class === "p1") {
            tokenClass = "p2";
        } else {
            tokenClass = "p1";
        }
    }
    let lastUncheckedInput = Array.from(uncheckedColInputs).pop();
    lastUncheckedInput.checked = true;
    let nextEl = lastUncheckedInput.nextElementSibling;
    if (nextEl.classList.contains('disc')) {
        nextEl.classList.add(tokenClass);
    }
}

function displayerErrors()
{
    return "Errors";
}

function tokenSound()
{
    const sound = new Audio('/assets/sounds/token-drop-c4.mp3');

    sound.play().catch(error => {
        console.log("Playback blocked until user interacts with the page:", error);
    });
}

function hydrateStateObj(resObj)
{
    board_state.player_id = resObj.data.player_id
    board_state.player_class = resObj.data.player_class
    board_state.room_ready = resObj.data.room_ready
    board_state.my_turn = resObj.data.my_turn
    board_state.tokens = resObj.data.tokens
    board_state.winner_id = resObj.data.winner_id
    board_state.board_finished = resObj.data.board_finished
}

function resetPolling()
{
    polling = false;
    clearInterval(poll_interval_id);
    poll_interval_id = null;
}

function disableBoard()
{
    document.getElementById("gameBoard").classList.add('disabled');
    inputs.forEach(radio => {
        radio.disabled = true;
    });
}

function enableBoard()
{
    document.getElementById("gameBoard").classList.remove('disabled');
    inputs.forEach(radio => {
        radio.disabled = false;
    });
}

function handleUserForm()
{
    let requiredData = {
        roomID: null
    };

    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById("joinCreateSelection").addEventListener('change', (e) => {
            document.getElementById("roomIdLabel").classList.toggle("show");
        });
    });

    document.getElementById("joinCreateForm").addEventListener('submit', (e) => {
        e.preventDefault();

        const action = document.getElementById("joinCreateSelection").value;

        if (action === 'join') {
            requiredData.roomID = getSetRoomID();
            requiredData.roomID
                ? join(requiredData.roomID).then(r => handleResponse(r))
                : displayErrorMsg('Room ID is non-existent');
        } else if (action === 'create') {
           create().then(r => handleResponse(r));
        }
    });
}

async function dropToken(boardID, playerID, column) {

    if (boardID !== null && (typeof boardID === "string" && boardID.trim() !== "") &&
        playerID !== null && (typeof playerID === "string" && playerID.trim() !== "") &&
        column !== null && (typeof column === "string" && column.trim() !== "")
    ) {
        const path = 'drop-token';
        const data = new URLSearchParams({ boardID: boardID, playerID: playerID, column: column });

        return await fetch(base_url + path, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data
        });
    }
}

async function create() {
    const path = 'create-room';
    return await fetch(base_url + path, {
        method: 'POST'
    });
}

async function join(boardID) {
    if (boardID !== null && (typeof boardID === "string" && boardID.trim() !== "")) {
        const path = 'join-room';
        const data = new URLSearchParams({ boardID: boardID});
        return await fetch(base_url + path + '?' + data);
    }
}

async function getState(boardID) {
    if (boardID !== null && (typeof boardID === "string" && boardID.trim() !== "")) {
        const path = 'get-board-state';
        const data = new URLSearchParams({ boardID: boardID});
        return await fetch(base_url + path + '?' + data);
    }
}

function getSetRoomID()
{
    const roomID = document.getElementById("roomID").value;
    if (roomID !== null && roomID !== '') {
        setRoomIDParam(roomID);
        return roomID;
    }

    return false;
}

function handleResponse(res) {
    res = JSON.parse(res.text());
    console.log(res);

    updateState(res);
    // update global state for other functions to use
}

function displayErrorMsg(msg)
{
    document.getElementById("error_alert").innerText = msg;
}

function clearErrorMsg()
{
    document.getElementById("error_alert").innerText = '';
}

function updateState(res) {
    board_state.id = res.id;
    board_state.current_player =  res.current_playerID;
    board_state.winner = res.winnerID;
    board_state.player1 = res.player1;
    board_state.player2 = res.player2;
    board_state.tokens = res.tokens;
}