<?php

class DatabaseAdaptor {
    private $DB; // The instance variable used in every method
    // Connect to an existing data based named 'first'
    public function __construct() {
        $dataBase = 'mysql:dbname=Checkers;charset=utf8;host=127.0.0.1';
        $user = 'root';
        $password ='';
        try {
            $this->DB = new PDO ( $dataBase, $user, $password );
            $this->DB->setAttribute ( PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION );
        } catch ( PDOException $e ) {
            echo ('Error establishing Connection');
            exit ();
        }
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
        $check = $this->DB->prepare("select GameID from Games 
                                    where PlayerBlack IS NULL");
        $check->execute();
        $arr = $check->fetchAll(PDO:: FETCH_ASSOC);
        //if a game exists with only one player, update it by adding the current player
        if(count($arr) > 0){
            $stmt = $this->DB->prepare('UPDATE Games 
                                        SET PlayerBlack = ? 
                                        WHERE GameID = ?');
            $stmt->execute(array($username, end($arr)));
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
        
        $stmt = $this->DB->prepare('insert into games (PlayerRed, Row0, Row1, Row2, Row3, Row4, Row5, Row6, Row7) 
                                    values (?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array($PlayerRed, $redRight, $redLeft, $redRight, $mid, $mid, $blackLeft, $blackRight, $blackLeft));
        
    }
    
    
    
    //returns json encoded nested array of strings that represent board state
    public function displayBoard($username) {
        //finds gameID for unfinished
        $check = $this->DB->prepare('select GameID from Games 
                                    where (PlayerRed = ? and winner IS NULL) 
                                    or (PlayerBlack = ? and winner IS NULL)'
                                    );
        $check->execute(array( $username, $username));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        
        //finds last game if no current
        if (count($arr) == 0) {
            $check = $this->DB->prepare('select GameID from Games
                                        where PlayerRed = ?
                                        or PlayerBlack = ?');
            $check->execute(array($username, $username));
            $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        }
        
        $curr = end($arr);
        $return = array();
        $check = $this->DB->prepare('select row0, row1, row2, row3, row4, row5, row6, row7 from Games 
                                    where GameID = ?');
        $check->execute(array(end($curr)));
        $currGame = $check->fetchAll( PDO:: FETCH_ASSOC );
        $cols = explode(',','row0,row1,row2,row3,row4,row5,row6,row7');
        for($i = 0; $i < 8;$i++){
            $return[$i] = str_split(end($currGame)[$cols[$i]]);
        }
        return json_encode($return);
    }
    
    
    
    //makes simple move or jump move happen in db, sends new board back when done
    public function move($oX, $oY, $mX, $mY, $username) {
        
        $check = $this->DB->prepare('select GameID from Games 
                                    where (PlayerRed = ?) 
                                    or (PlayerBlack = ?)');
        $check->execute(array($username, $username));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        
        //pulls row piece is moving from and to
        $firstRow = "Row" + strval($oX);
        
        //row name, gameID
        $rowCall = $this->DB->prepare('select ? from Games where GameID = ?');
        $rowCall->execute(array($firstRow, end($arr)));
        $rowOne = $firstRowCall->fetchAll( PDO:: FETCH_ASSOC )[0];
        $secondRow = "Row" + strval($mX);
        $secondRowCall->execute(array($secondRow, end($arr)));
        $rowTwo = $secondRowCall->fetchAll( PDO:: FETCH_ASSOC )[0];
        $updatedOne = "";
        $updatedTwo = "";
        
        //row name, string of row, gameID
        $pushRow = $this->DB->prepare('update Games set ? = ? where GameID = ?');
        
        
        //reconstruct rows with simple move
        for($i = 0; $i < 8; $i++) {
            
            //from row move
            if ($i == $oY) {
                $updatedOne = $updatedOne + "0";
            }
            else {
                $updatedOne = $updatedOne + substr($rowOne, $i, 1);
            }
            
            //to row move
            if ($i == $mY) {
                $updatedTwo = $updatedTwo + substr($rowOne, $oY, 1);
            }
            else {
                $updatedTwo = $updatedTwo + substr($rowTwo, $i, 1);
            }
        }
        
        //jump move
        if (($oX - $mX == 2 || $oX - $mX == -2) && ($oY - $mY == 2 || $oY - $mY == -2)) {
            $thirdRow = "Row" + strval(($oX+$mX)/2);
            $rowCall->execute(array($thirdRow, end($arr)));
            $rowThree = $rowCall->fetchAll( PDO:: FETCH_ASSOC )[0];
            $updatedThree = "";
            
            //reconstruct middle row with move
            for ($i = 0; $i < 8; $i++) {
                if ($i == ($oY+$mY)/2) {
                    $updatedThree = $updatedThree + "0";
                }
                else {
                    $updatedThree = $updatedThree + substr($rowThree, $i, 1);
                }
                
            }
            
            //update middle row
            $pushRow->execute(array($thirdRow, $updatedThree, end($arr)));
            
        }
        
        //updates rows in db
        $uOne->execute(array($firstRow, $updatedOne, end($arr)));
        $uTwo->execute(array($secondRow, $updatedTwo, end($arr)));
        
        return $this->displayBoard($username);
    }
    
    
    //passes username of winner, null if not over
    public function isGameOver($username) {
        $check = $this->DB->prepare("select row0, row1, row2, row3, row4, row5, row6, row7 from Games 
                                    WHERE PlayerRed = ? 
                                    AND winner IS NULL");
        $check->execute(array($username));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        $player = "0";
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select row0, row1, row2, row3, row4, row5, row6, row7 from Games 
                                        WHERE PlayerBlack = ? 
                                        AND winner IS NULL");
            $check2->execute(array($username));
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
            $player = "2";
        }
        else {
            $player = "1";
        }
        
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select winner from Games
                                        where (PlayerBlack = ? 
                                        OR PlayerRed = ?) 
                                        AND winner IS NOT NULL");
            $check2->execute(array($username, $username));
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
            return end($arr);
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

        //return values:
            //Playername of winning player
            //null if game not over
        $guid;
        if ($player == "1") {
            $up = $this->DB->prepare("select GameID from Games 
                                    WHERE PlayerRed = ? 
                                    AND where winner IS NULL");
            $up->execute(array($username));
            $guid = end($up->fetchAll( PDO:: FETCH_ASSOC ));
            
        }
        if ($player == "2") {
            $up2 = $this->DB->prepare("select GameID from Games 
                                    WHERE PlayerBlack = ? 
                                    AND where winner IS NULL");
            $up2->execute(array($username));
            $guid = end($up2->fetchAll( PDO:: FETCH_ASSOC ));
        }
        
        //if both players still have pieces, return null
        if ($other != 0 && $player != 0) {
            return null;
        }
        
        //assign winner
        else {
            if ($other == 0) {
                $up = $this->DB->prepare("update Games set winner = ? 
                                        WHERE GameID = ? 
                                        AND winner IS NULL");
                $up->execute(array($username, $guid));
                return $username;
            }
            
            if ($playerScore == 0) {
                $otherPlayer = $this->getOtherPlayer($username);
                $up = $this->DB->prepare("update Games set winner = ? 
                                        WHERE GameID = ? 
                                        AND winner IS NULL");
                $up->execute(array($otherPlayer, $guid));
                return $otherPlayer;
                
            }
        }
        
        //shouldn't get here
        return "messed up somewhere";
    }
    
    
    
    //gets other user for front end
    public function getOtherPlayer($username) {
        $check = $this->DB->prepare("select PlayerBlack from Games 
                                    where PlayerRed = ? 
                                    AND winner IS NULL");
        $check->execute(array($username));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select PlayerRed from Games 
                                        WHERE PlayerBlack = ? 
                                        AND winner IS NULL");
            $check2->execute(array($username));
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
        }
        return end($arr);
    }
    
    
    //sets game winner to other player, returns success string
    public function resign($username) {
        $winner = $this->getOtherPlayer($username);
        $check = $this->DB->prepare("update Games 
                                    SET winner = ? 
                                    where (PlayerRed = ? OR PlayerBlack = ?) 
                                    AND winner IS NULL");
        $check->execute(array($winner, $username, $username));
        return "resigned successfully";
    }
    
    
    
    //finds current player
    public function isActivePlayer($username) {
        $check = $this->DB->prepare("SELECT activePlayer from Games 
                                    where (PlayerRed = ? OR PlayerBlack = ?)
                                    AND winner IS NULL");
        $check->execute(array($username, $username));
        $game = $check->fetchAll( PDO:: FETCH_ASSOC );
        if (end($game) == $username) {
            return true;
        }
        else {
            return false;
        }
    }
}

?>