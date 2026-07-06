let polling, poll_interval_id, inputs, board_state, abort_controller;
board_state = {
    player_id: null,
    winner_id: null,
    player_class: null,
    room_ready: false,
    my_turn: false,
    board_finished: false,
    tokens: [],
};

inputs = document.querySelectorAll('#gameBoard form .board .column input[type=radio]');

poll_interval_id = null;
const base_uri = window.location.protocol + "//" + window.location.hostname + "/api";
const room_id = getRoomID();
const setInputsClickEvent = (e) => {
    e.preventDefault();
    const col = parseInt(e.currentTarget.parentElement.dataset.col);
    if (! (document.querySelectorAll(`form .board .grid .column[data-col="${col}"] input[type=radio]:checked`).length >= 6) ) {
        setToken(col);
    }
}

// entry/init
document.addEventListener("DOMContentLoaded", () => {
    startPolling();
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
        board_state.player_id = resObj.data.player_id
        board_state.player_class = resObj.data.player_class
        board_state.room_ready = resObj.data.room_ready
        board_state.my_turn = resObj.data.my_turn
        board_state.tokens = resObj.data.tokens
        board_state.winner_id = resObj.data.winner_id
        board_state.board_finished = resObj.data.board_finished
        document.getElementById("gameBoard").classList.add(board_state.player_class);
        if (!board_state.my_turn) {
            document.getElementById("gameBoard").classList.add('disabled');
        } else {
            document.getElementById("gameBoard").classList.remove('disabled');
        }
        if (board_state.winner_id !== null) {
            alert("Winner " + board_state.winner_id);
        }
        renderBoard(board_state.tokens);
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

    inputs.forEach(radio => {
        radio.addEventListener('click', setInputsClickEvent, { signal: abort_controller.signal })
    });
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