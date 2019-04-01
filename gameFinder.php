<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Game</title>
<link rel="stylesheet" href="game.css">
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
		window.location = "game.php";

        ajax.open("POST", "controller.php", true);
	    ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    ajax.send("username=" + username + "&password=" + password + "&method=searchGame");
	    ajax.onreadystatechange = function() {
	        if (ajax.readyState == 4 && ajax.status == 200){
	        	if (ajax.responseText == true){

					

					window.location = "game.php";
	        	}
	        	else{
	        		document.getElementById("messages").innerHTML = "Could not find a game";
	        	}
	        }
	    };
    }

</script>
</html>
