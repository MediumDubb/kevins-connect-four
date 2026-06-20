<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Title</title>
    <!-- Link your CSS stylesheet here -->
    <!-- <link rel="stylesheet" href=""> -->
</head>
<body>

<!-- Your web page content goes here -->
<div class="container">
    <div class="flex-center">
        <div class="form-container">
            <h1>Kevin's Connect4</h1>
            <form action="/assign" method="post">
                <label for="playerName">Name*:</label>
                <input type="text" id="playerName" name="playerName" placeholder="Enter Player Name">
                <label for="matchID">Join:</label>
                <input type="text" id="boardID" name="boardID" placeholder="Board ID">

                <input type="submit" value="Go">
            </form>
        </div>
    </div>
</div>
<!-- Link your JavaScript file here -->
<script type="text/javascript" src="http://c4.local/assets/javascript/game-script.js"></script>
</body>
</html>
