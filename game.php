<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Game</title>
</head>

<?php session_start()?>

<style>

    .redWithPiece {
      width: 50px;
      height: 50px;
      -webkit-border-radius: 25px;
      -moz-border-radius: 25px;
      border-radius: 25px;
      background: red;
    }

    .boardStyle{
        width : 100%;
        height: 100%;
    }

    table{
        border-collapse : collapse;
    }

    td{
        min-width: 50px;
        max-width : 50px;
        height : 50px;
        max-height : 50px;
        border : 2px solid black;
        text-align: center;
    }

    .red{
        background-color:gray;
    }
    .black{
        background-color:white;
    }
    .redPiece{
        color : red;
        content: ' \25CF';
        font-size: 60px;
    }
    .blackPiece{
        color : black;
        font-size: 60px;
    }
    .selectedPiece{
        color : green;
        font-size: 60px;
    }
    
</style>

<body>
    <div id="header">
	<h1 class = "title">Checkers with Friends</h1>
	<p class = "centered">Welcome <?php echo $_SESSION["user"] ?></p>
    <input type="button" onclick="displayBoard()" value="check for opponents move" id="checkForMove">
    </div>

    <div id="boardDiv" class="boardStyle">
        <table id="board">
        
        </table>    
    </div>
</body>


<script>
    
    var selected = null;

    function displayBoard() {
        var circle = '&#9679;'
        var ajax = new XMLHttpRequest();
	    ajax.open("POST", "controller.php", true);
	    ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    ajax.send("method=displayBoard");
	    ajax.onreadystatechange = function() {
	        if (ajax.readyState == 4 && ajax.status == 200) {
                //alert(ajax.responseText);
	        	var boardState = JSON.parse(ajax.responseText);
                //alternating variable to handle switching background color
                var board = document.getElementById("board");
                //remove all prior board state
                while (board.firstChild) {
                     board.removeChild(board.firstChild);
                }
                for (var y = 0; y < 8; y++){
                    let tempRow = board.insertRow(y);
                    for(var x = 0; x < 8; x++){
                        let tempData = tempRow.insertCell(x);
                        //set background colors
                        if ((x + y) % 2 == 0){
                            tempData.classList.add("black");
                        }
                        else{
                            tempData.classList.add("red");
                        }
                        //set piece contents
                        if (boardState[y][x] == 1){
                            tempData.innerHTML = '&#9679';
                            tempData.classList.add("redPiece");
                        }
                        else if (boardState[y][x] == 2){
                            tempData.innerHTML = '&#9679';
                            tempData.classList.add("blackPiece");
                        }
                    }
                }

                document.getElementById("board").onclick = function(e) {
                    select(e.target);   
                }
	        }
	    }; 
    }

    function select(selectedTile){
        //if new tile contains a piece and no tile was previously selected, highlight the new piece and set it to selected
        if (selected == null){
            if(selectedTile.classList.contains("redPiece") || selectedTile.classList.contains("blackPiece")){
                selected = selectedTile;
                selectedTile.classList.add("selectedPiece");
            }
            else{
                return;
            }
        }
        
        //if a tile was previously selected
        else{
            var oY = selected.parentNode.rowIndex;
            var oX = selected.cellIndex;
            var nY = selectedTile.parentNode.rowIndex;
            var nX = selectedTile.cellIndex;
            //alert('oY : ' + oY + '\n' + 'oX : ' + oX + '\n' + 'nY : ' + nY + '\n' + 'nX : ' + nX + '\n');

            selectedTile.classList.remove("selectedPiece");
            selected = null;
            var ajax = new XMLHttpRequest();
            ajax.open("POST", "controller.php", true);
            ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            ajax.send("method=move&oX=" + oX + "&oY=" + oY + "&nX=" + nX + "&nY=" + nY);
            ajax.onreadystatechange = function() {
                if (ajax.readyState == 4 && ajax.status == 200) {
                    //alert(ajax.repsonseText);
                    displayBoard();
                }
            }
        }   
    }

</script>
</html>
