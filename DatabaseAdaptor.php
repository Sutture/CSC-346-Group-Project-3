<?php

ini_set('display_errors', 1);


class databaseAdaptor {
    private $DB; // The instance variable used in every method
    // Connect to an existing data based named 'first'
    public function __construct() {
        //////////////////////////////////////////////
        $dbhost = 'csc346checkers.cf9rn5kk9dft.us-east-1.rds.amazonaws.com';
        $dbport = '3306';
        $dbname = 'Checkers';
        $charset = 'utf8' ;
        
        $dsn = "mysql:host={$dbhost};port={$dbport};dbname={$dbname};charset={$charset}";
        $username = 'csc346';
        $password = 'Borderlands1';
        
        $this->DB = new PDO($dsn, $username, $password);
    }
    
    public function checkForUsername($username){
        $stmt = $this->DB->prepare( "SELECT username FROM userData where username = '" . $username . "'");
        $stmt->execute ();
        return $stmt->fetchAll( PDO::FETCH_ASSOC );
    }
    public function insertNewUser($username , $password){
        $stmt = $this->DB->prepare("insert into userData (username, password) values ('" . $username . "' , '" . $password . "')");
        $stmt->execute();
    }
    
    public function checkUserCredentials($username, $password){
        $stmt = $this->DB->prepare("select password from userData where username = ('" . $username ."')");
        $stmt->execute();
        $arr = $stmt->fetchAll( PDO::FETCH_ASSOC );
        return $arr[0]["password"] == $password;
    }
    
    
    //
    public function searchGames($username){
        //find a game without a second player
        $check = $this->DB->prepare("select gameid from Games 
                                    where playerblack is NULL");
        $check->execute();
        $arr = $check->fetchAll(PDO:: FETCH_ASSOC);
        //if a game exists with only one player, update it by adding the current player
        if(count($arr) > 0){
            $stmt = $this->DB->prepare('update Games 
                                        set playerblack = ? 
                                        where gameid = ?');
            $stmt->execute(array($username, end($arr)["gameID"]));
        } 
        //if no games exist with only one player, make a new game
        else{
            $this->newGameBoard($username);
        }
    }
    
    
    
    //makes new game, with new board and given player assigned to player 1
    public function newGameBoard($PlayerRed) {
        $redRight = "01010101";
        $redLeft = "10101010";
        $mid = "00000000";
        $blackLeft = "20202020";
        $blackRight = "02020202";
        
        $stmt = $this->DB->prepare('insert into Games (playerred, activeplayer, row0, row1, row2, row3, row4, row5, row6, row7) 
                                    values (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array($PlayerRed, $PlayerRed, $redRight, $redLeft, $redRight, $mid, $mid, $blackLeft, $blackRight, $blackLeft));
    }
    
    
    
    //echos json encoded nested array of strings that represent board state
    public function displayBoard($username) {
        //finds gameID for unfinished
        $check = $this->DB->prepare('select gameid from Games 
                                    where (playerred = ? or playerblack = ?) 
                                    and winner is NULL');
        $check->execute(array($username, $username));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        
        //finds last game if no current
        if (count($arr) == 0) {
            $check = $this->DB->prepare('select gameid from Games 
                                        where playerred = ? 
                                        or playerblack = ?');
            $check->execute(array($username, $username));
            $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        }
        
        $check = $this->DB->prepare('select row0, row1, row2, row3, row4, row5, row6, row7 from Games 
                                    where gameid = ?');
        $check->execute(array($arr[0]["gameid"]));
        $currGame = $check->fetchAll( PDO:: FETCH_ASSOC );
        $cols = explode(',','row0,row1,row2,row3,row4,row5,row6,row7');
        $echo = array();
        for($i = 0; $i < 8;$i++){
            $echo[$i] = str_split($currGame[0][$cols[$i]]);
        }
        echo json_encode($echo);
    }
    
    
    
    //makes simple move or jump move happen in db, sends new board back when done
    public function move($oX, $oY, $nX, $nY, $username) {
        //get current game
        $check = $this->DB->prepare('select gameid, playerred, playerblack, activeplayer, row0, row1, row2, row3, row4, row5, row6, row7 from Games 
                                    where (playerred = ? or playerblack = ?) 
                                    and winner is NULL');
        $check->execute(array($username, $username));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );

        $gameID = $arr[0]['GameID'];

        echo "current user: " . $username . "  active player: " . $arr[0]['activePlayer'] . '\n';
        if($username == $arr[0]['activePlayer']){
            //break game into 2d array
            $boardState = array();
            for($i = 0; $i < 8;$i++){
                $boardState[$i] = str_split(end($arr)['row'.$i]);
            }

            //echo 'before state ';
            //echo json_encode($boardState);

            $oldPosition = $boardState[$oY][$oX];
            $newPosition = $boardState[$nY][$nX];

            echo 'From: ('.$oX.','.$oY.')\nTo:('.$nX.','.$nY.')';

            //if there was no piece in the first pos, do nothing
            if ($oldPosition != 0){
                //if the position to move to is occupied, do nothing
                if ($newPosition == 0){
                    //logic for red side move
                    if($username == $arr[0]['PlayerRed']){
                        //only 4 possible move locations
                        //up right
                        if($nX == $oX + 1 && $nY == $oY + 1){
                            $boardState[$oY][$oX] = 0;
                            $boardState[$nY][$nX] = 1;
                            echo $this->pushBoardState($username, $this->getOtherPlayer($gameID, $username), $boardState, $gameID);
                        }
                        //up left
                        else if($nX == $oX - 1 && $nY == $oY + 1){
                            $boardState[$oY][$oX] = 0;
                            $boardState[$nY][$nX] = 1;
                            echo $this->pushBoardState($username, $this->getOtherPlayer($gameID, $username), $boardState, $gameID);
                        }
                        //up right jump
                        else if($nX == $oX + 2 && $nY == $oY + 2){
                            $boardState[$oY][$oX] = 0;
                            $boardState[$nY][$nX] = 1;
                            //remove the jumped piece
                            $boardState[$oY + 1][$oX + 1] = 0;
                            echo $this->pushBoardState($username, $this->getOtherPlayer($gameID, $username), $boardState, $gameID);
                        }
                        //up left jump
                        else if($nX == $oX - 2 && $nY == $oY + 2){
                            $boardState[$oY][$oX] = 0;
                            $boardState[$nY][$nX] = 1;
                            //remove the jumped piece
                            $boardState[$oY + 1][$oX - 1] = 0;
                            echo $this->pushBoardState($username, $this->getOtherPlayer($gameID, $username), $boardState, $gameID);
                        }
                        else{
                            echo "attempt move to invalid location";
                        }
                    }
                    //logic for black side move
                    else if ($username == $arr[0]["PlayerBlack"]){
                        //down right
                        if($nX == $oX + 1 && $nY == $oY - 1){
                            $boardState[$oY][$oX] = 0;
                            $boardState[$nY][$nX] = 2;
                            echo $this->pushBoardState($username, $this->getOtherPlayer($gameID, $username), $boardState, $gameID);
                        }
                        //down left
                        else if($nX == $oX - 1 && $nY == $oY - 1){
                            $boardState[$oY][$oX] = 0;
                            $boardState[$nY][$nX] = 2;
                            echo $this->pushBoardState($username, $this->getOtherPlayer($gameID, $username), $boardState, $gameID);
                        }
                        //down right jump
                        else if($nX == $oX + 2 && $nY == $oY - 2){
                            $boardState[$oY][$oX] = 0;
                            $boardState[$nY][$nX] = 2;
                            //remove the jumped piece
                            $boardState[$oY - 1][$oX + 1] = 0;
                            echo $this->pushBoardState($username, $this->getOtherPlayer($gameID, $username), $boardState, $gameID);
                        }
                        //down left jump
                        else if($nX == $oX - 2 && $nY == $oY - 2){
                            $boardState[$oY][$oX] = 0;
                            $boardState[$nY][$nX] = 2;
                            //remove the jumped piece
                            $boardState[$oY - 1][$oX - 1] = 0;
                            echo $this->pushBoardState($username, $this->getOtherPlayer($gameID, $username), $boardState, $gameID);
                        }
                        else{echo "attempt move to invalid location";}
                    }
                    else{echo "error: current player not assigned to game";}
                }
                else{echo "cant move to occupied space";}
            }
            else{echo "no piece was selected";}
        }
        else{echo "not your turn";}
    }  
    
    public function pushBoardState($username, $activePlayer, $boardStateArray, $gameID){
        //convert int array into array of row strings
        $boardStrings = array();
        for($y = 0; $y < 8; $y++){
            $rowString = "";
            for($x = 0; $x < 8; $x++){
                $rowString = $rowString . $boardStateArray[$y][$x];
            }   
            $boardStrings[$y] = $rowString;
        }

        echo json_encode($boardStrings);

        $check = $this->DB->prepare('UPDATE Games
                                    SET activePlayer = ?, 
                                    row0 = ?,
                                    row1 = ?, 
                                    row2 = ?, 
                                    row3 = ?, 
                                    row4 = ?, 
                                    row5 = ?, 
                                    row6 = ?, 
                                    row7 = ?
                                    WHERE gameid = ?');
            try{$check->execute(array($activePlayer, $boardStrings[0], $boardStrings[1], $boardStrings[2], $boardStrings[3], $boardStrings[4], $boardStrings[5], $boardStrings[6], $boardStrings[7], $gameID));}
                catch( PDOException $e){
                    echo $e;
                }
        
    }

    //passes username of winner, NULL if not over
    public function isGameOver($username) {
        $check = $this->DB->prepare("select row0, row1, row2, row3, row4, row5, row6, row7 from Games 
                                    where playerred = ? 
                                    and winner is NULL");
        $check->execute(array($username));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        $player = "0";
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select row0, row1, row2, row3, row4, row5, row6, row7 from Games 
                                        where playerblack = ? 
                                        and winner is NULL");
            $check2->execute(array($username));
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
            $player = "2";
        }
        else {
            $player = "1";
        }
        
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select winner from Games
                                        where (playerblack = ? 
                                        or playerred = ?) 
                                        and winner is not NULL");
            $check2->execute(array($username, $username));
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
            echo end($arr);
        }
        
        //increments current number of pieces for each person
        $other = 0;
        $playerScore = 0;
        for($row = 0; $row < 8; $row++) {
            for($col = 0; $col < 8; $col++) {
                if(substr($arr[$row], $col, 1) == $player) {
                    ++$playerScore;
                }
                if(substr($arr[$row], $col, 1) != $player && substr($arr[$row], $col, 1) != "0") {
                    ++$other;
                }
            }
        }
        
        //finds current gameID

        //echo values:
            //Playername of winning player
            //NULL if game not over
        $guid;
        if ($player == "1") {
            $up = $this->DB->prepare("select gameid from Games 
                                    where playerred = ? 
                                    and where winner is NULL");
            $up->execute(array($username));
            $guid = end($up->fetchAll( PDO:: FETCH_ASSOC ));
            
        }
        if ($player == "2") {
            $up2 = $this->DB->prepare("select gameid from Games 
                                    where playerblack = ? 
                                    and where winner is NULL");
            $up2->execute(array($username));
            $guid = end($up2->fetchAll( PDO:: FETCH_ASSOC ));
        }
        
        //if both players still have pieces, echo NULL
        if ($other != 0 && $player != 0) {
            echo NULL;
        }
        
        //assign winner
        else {
            if ($other == 0) {
                $up = $this->DB->prepare("update Games set winner = ? 
                                        where gameid = ? 
                                        and winner is NULL");
                $up->execute(array($username, $guid));
                echo $username;
            }
            
            if ($playerScore == 0) {
                $otherPlayer = $this->getOtherPlayer($gameID, $username);
                $up = $this->DB->prepare("update Games set winner = ? 
                                        where gameid = ? 
                                        and winner is NULL");
                $up->execute(array($otherPlayer, $guid));
                echo $otherPlayer;
                
            }
        }
        //shouldn't get here
        echo "messed up somewhere";
    }
    
    
    
    //gets other user for front end
    public function getOtherPlayer($gameID, $currentPlayer) {
        $check = $this->DB->prepare('select playerred, playerblack from Games 
                                    where gameid = ?');
        $check->execute(array($gameID));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        if($currentPlayer == $arr[0]['PlayerRed']){
            return $arr[0]['PlayerBlack'];
        }
        else if ($currentPlayer == $arr[0]['PlayerBlack']){
            return $arr[0]['PlayerRed'];
        }
        else{
            echo "error line 341";
        }
    }
    
    
    
    //sets game winner to other player, echos success string
    public function resign($username) {
        $winner = $this->getOtherPlayer($gameID, $username);
        $check = $this->DB->prepare("UPDATE Games 
                                    SET winner = ? 
                                    where (playerred = ? OR playerblack = ?) 
                                    AND winner IS NULL");
        $check->execute(array($winner, $username, $username));
        echo "resigned successfully";
    }
    
    
    
    //finds current player
    public function isActivePlayer($username) {
        $check = $this->DB->prepare("SELECT activeplayer from Games 
                                    where (playerred = ? OR playerblack = ?)
                                    AND winner IS NULL");
        $check->execute(array($username, $username));
        $game = $check->fetchAll( PDO:: FETCH_ASSOC );
        if (end($game) == $username) {
            echo true;
        }
        else {
            echo false;
        }
    }
}

?>