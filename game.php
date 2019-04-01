<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Game</title>
<link rel="stylesheet" href="game.css">
</head>

<?php session_start()?>

<body>
    <div id="header">
	<h1 class = "title">Checkers with Friends</h1>
	<p class = "centered">Welcome <?php echo $_SESSION["user"] ?></p>
    <input type="button" onclick="displayBoard()" value="check for opponents move" id="checkForMove">
    </div>

    <div id="board" class="boardStyle"><div>


</body>


<script>

    function displayBoard(){
        var boardState;
        var ajax = new XMLHttpRequest();
	    ajax.open("POST", "controller.php", true);
	    ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    ajax.send("method=displayBoard");
	    ajax.onreadystatechange = function() {
	        if (ajax.readyState == 4 && ajax.status == 200) {
	        	boardState = ajax.responseText;

                //draw board
                var table = document.createElement('table');
                for(var y = 0; y < 8; y++){
                    var tempRow = document.createElement('tr');
                    for (var x = 0; x < 8; x++){
                        tempRow.appendChild(document.createElement('td'));
                        if (boardState[x][y] == 1){
                            tempRow.classList.add('redPiece');
                        }
                        if(boardState[x][y] == 2){
                            tempRow.classList.add('blackPiece');
                        }
                    }
                    table.appendChild(tempRow);
                }

                //add to DOM
                document.getElementById('board').innerHTML = table;
	        }
	    }; 
    }

</script>
</html>
