<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Title</title>
    <!-- Link your CSS stylesheet here -->
    <link rel="stylesheet" href="http://c4.local/assets/css/dist/app.min.css">
</head>
<body>

<!-- Your web page content goes here -->
<div class="container" id="portal">
    <div class="flex-center">
        <div class="form-container">
            <h1>Kevin's Connect4</h1>
            <form action="/room/init" method="POST">
                <label for="playerName">*Player name:</label>
                <input type="text" id="playerName" name="playerName" placeholder="Enter Player Name" required>
                <label for="joinCreate">*I want to: </label>
                <select name="joinCreate" id="joinCreate" required>
                    <option value="create" selected> Create a Room</option>
                    <option value="join"> Join a Room</option>
                </select>
                <label for="roomID" id="roomIdLabel" class="hide">Specify room:
                    <input type="text" id="roomID" name="roomID" placeholder="Room ID">
                </label>

                <input type="submit" value="Go">
            </form>
        </div>
    </div>
</div>
<script type="text/javascript" src="http://c4.local/assets/javascript/form-script.js"></script>
</body>
</html>
