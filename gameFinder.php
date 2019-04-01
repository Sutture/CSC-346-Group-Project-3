<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Game</title>
</head>

<?php session_start()?>

<body>

	<h1 class = "title">Checkers with Friends</h1>
	<p class = "centered">Welcome <?php echo $_SESSION["user"] ?></p>
    <p class = "centered">Start a game?  <input type="button" id="startGame" onclick="searchGame()" value="Start"></p>
    </div>
</body>


<script>

    function searchGame(){
		//FOR TESTING PURPOSES ONLY
		var ajax = new XMLHttpRequest();
		ajax.open("POST", "controller.php", true);
	    ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    ajax.send("method=searchGames");
	    ajax.onreadystatechange = function() {
	        if (ajax.readyState == 4 && ajax.status == 200){
				window.location = "game.php";
	        }
	    };
    }

</script>
</html>
