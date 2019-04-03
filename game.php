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
        background-color:red;
    }
    .black{
        background-color:black;
    }
    .redPiece{
        color : blue;
        content: ' \25CF';
        font-size: 60px;
    }
    .blackPiece{
        background-color:red;
        color : white;
        font-size: 60px;
    }
    .selectedPiece{
        background-color:red;
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
                alert(ajax.responseText);
	        	var boardState = JSON.parse(ajax.responseText);
                //alternating variable to handle switching background color
                var board = document.getElementById("board");
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
            var validated = false;
            var oY = selected.parentNode.rowIndex;
            var oX = selected.cellIndex;
            var nY = selectedTile.parentNode.rowIndex;
            var nX = selectedTile.cellIndex;
            alert('oY : ' + oY + '\n' + 'oX : ' + oX + '\n' + 'nY : ' + nY + '\n' + 'nX : ' + nX + '\n');
            //all values inside board
            if (oY >= 0 && oY < 8 && oX >= 0 && oX < 8 && nY >= 0 && nY < 8 && nX >= 0 && nX < 8){
                //red side move validation
                if(selected.classList.contains("redPiece")){
                    if (nY == oY + 1){
                        if (nX == oX + 1 || nX == oX -1){
                            validated = true;
                        }
                    }
                }
                else if (selected.classList.contains("blackPiece")){
                    if (nY == oY - 1){
                        if (nX == oX + 1 || nX == oX -1){
                            validated = true;
                        }
                    }
                }
            }

            if(validated){
                selectedTile.classList.remove("selectedPiece");
                selected = null;
                var ajax = new XMLHttpRequest();
                ajax.open("POST", "controller.php", true);
                ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                ajax.send("method=move&oX=" + oX + "&oY=" + oY + "&nX=" + nX + "&nY=" + nY);
                ajax.onreadystatechange = function() {
                    if (ajax.readyState == 4 && ajax.status == 200) {
                        




                        displayBoard();
                    }
                }
            }
            else{
                selected.classList.remove("selectedTile");
                selected = null;
            }
        }   
    }

</script>
</html>
