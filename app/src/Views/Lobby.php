<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Title</title>
    <!-- Link your CSS stylesheet here -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<p><?php echo $this->db->test_connection() ?></p>
<div class="container">
    <div class="flex-center">
        <div class="form-container">
            <h1>Kevin's Connect4</h1>
            <form action="/assign" method="post">
                <label for="playerName">Name*:</label>
                <input type="text" id="playerName" name="playerName" placeholder="Enter Player Name">
                <label for="color">Choose Color*:</label>
                <select name="color" id="color">
                    <option value="red">Red</option>
                    <option value="orange">Orange</option>
                    <option value="yellow">Yellow</option>
                    <option value="green">Green</option>
                    <option value="blue">Blue</option>
                    <option value="indigo">Indigo</option>
                    <option value="violet">Violet</option>
                </select>
                <label for="matchID">Join:</label>
                <input type="text" id="matchID" name="matchID" placeholder="Match ID">

                <input type="submit" value="Join Game">
            </form>
        </div>
    </div>
</div>


<!-- Link your JavaScript file here -->
</body>
</html>
