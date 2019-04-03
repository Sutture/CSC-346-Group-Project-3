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
        $check = $this->DB->prepare("select GameID from Games 
                                    where PlayerBlack is null");
        $check->execute();
        $arr = $check->fetchAll(PDO:: FETCH_ASSOC);
        if(count($arr) > 0){
            $stmt = $this->DB->prepare("update Games set PlayerBlack = '" . $username . "' where GameID = '" . end($arr) . "'");
            $stmt->execute();
        } else{
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
            values (?,?,?,?,?,?,?,?)');
        $stmt->bindParam("ssssssss", $PlayerRed, $redRight, $redLeft, $redRight, $mid, $mid, $blackLeft, $blackRight, $blackLeft);
        $stmt->execute();
        
    }
    
    
    
    //returns json encoded nested array of strings that represent board state
    public function displayBoard($username) {
        
        //finds gameID for unfinished
        $check = $this->DB->prepare('select GameID from Games 
                                    where (PlayerRed = ? and winner = ?) 
                                    or (PlayerBlack = ? and winner = ?)'
                                    );
        $check->execute(array( $username, null, $username, null));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        
        //finds last game if no current
        if (count($arr) == 0) {
            $check = $this->DB->prepare('select GameID from Games
                                        where PlayerRed is ? 
                                        or PlayerBlack is ?)');
            $check->execute(array($username, $username));
            $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        }
        
        $curr = end($arr);
        $return = array();
        $check = $this->DB->prepare('select row0, row1, row2, row3, row4, row5, row6, row7 from Games where GameID is ?');
        $check->execute(array($curr));
        $currGame = $check->fetchAll( PDO:: FETCH_ASSOC );
        for($x = 0; $x < 8; $x++) {
            $add = array();
            for($y = 0; $y < 8; $y++) {
                $add[] = substr($currGame[$x], $y, 1);
            }
            $return[] = $add;
        }
        
        return json_encode($return);
    }
    
    
    
    //makes simple move or jump move happen in db, sends new board back when done
    public function move($oX, $oY, $mX, $mY, $username) {
        
        $check = $this->DB->prepare('select GameID from Games 
                                    where (PlayerRed is ?) 
                                    or (PlayerBlack is ?)');
        $check->execute(array($username, $username));
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        
        //pulls row piece is moving from and to
        $firstRow = "Row" + strval($oX);
        $firstRowCall = $this->DB->prepare('select "' . $firstRow . '" from Games where GameID is "' . end($arr) . '"');
        $firstRowCall->execute();
        $rowOne = $firstRowCall->fetchAll( PDO:: FETCH_ASSOC )[0];
        $secondRow = "Row" + strval($mX);
        $secondRowCall = $this->DB->prepare('select "' . $secondRow . '" from Games where GameID is "' . end($arr) . '"');
        $secondRowCall->execute();
        $rowTwo = $secondRowCall->fetchAll( PDO:: FETCH_ASSOC )[0];
        $updatedOne = "";
        $updatedTwo = "";
        
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
            $thirdRowCall = $this->DB->prepare('select "' . $thirdRow . '" from Games where GameID is "' . end($arr) . '"');
            $thirdRowCall->execute();
            $rowThree = $thirdRowCall->fetchAll( PDO:: FETCH_ASSOC )[0];
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
            $uThree = $this->DB->prepare('update Games set "' . $thirdRow . '" = "' . $updatedThree . '" where GameID is "' . end($arr) . '"');
            $uThree->execute();
            
        }
        
        //updates rows in db
        $uOne = $this->DB->prepare('update Games set "' . $firstRow . '" = "' . $updatedOne . '" where GameID is "' . end($arr) . '"');
        $uOne->execute();
        $uTwo = $this->DB->prepare('update Games set "' . $secondRow . '" = "' . $updatedTwo . '" where GameID is "' . end($arr) . '"');
        $uTwo->execute();
        
        return $this->displayBoard($username);
    }
    
    
    
    //legacy update, now taken care of in move
    public function updateBoard($username, $push) {
        
        $check = $this->DB->prepare('select GameID from Games where PlayerRed is "' . $username . '" and winner is null');
        $check->execute();
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare('select GameID from Games where PlayerBlack is "' . $username . '" and winner is null');
            $check2->execute();
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
        }
        
        $update = $this->DB->prepare("update Games set Board = '" . $push . "' where GameID = '" . end($arr) . "'");
        $update->execute();
    }
    
    
    
    //passes username of winner, null if not over
    public function isGameOver($username) {
        
        $check = $this->DB->prepare("select row0, row1, row2, row3, row4, row5, row6, row7 from Games where PlayerRed is '" . $username . "' and winner is null");
        $check->execute();
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        $player = "0";
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select row0, row1, row2, row3, row4, row5, row6, row7 from Games where PlayerBlack is '" . $username . "' and winner is null");
            $check2->execute();
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
            $player = "2";
        }
        else {
            $player = "1";
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
        $guid;
        if ($player == "1") {
            $up = $this->DB->prepare("select GameID from Games where PlayerRed is '" . $username . "' and where winner is null");
            $up->execute();
            $guid = end($up->fetchAll( PDO:: FETCH_ASSOC ));
            
        }
        if ($player == "2") {
            $up2 = $this->DB->prepare("select GameID from Games where PlayerBlack is '" . $username . "' and where winner is null");
            $up2->execute();
            $guid = end($up2->fetchAll( PDO:: FETCH_ASSOC ));
        }
        
        //if both players still have pieces, return null
        if ($other != 0 && $player != 0) {
            return null;
        }
        
        //assign winner
        else {
            if ($other == 0) {
                $up = $this->DB->prepare("update Games set winner = '" . $username . "' where GameID is '" . $guid . "' and where winner is null");
                $up->execute();
                return $username;
            }
            
            if ($playerScore == 0) {
                $otherPlayer = $this->getOtherPlayer($username);
                $up = $this->DB->prepare("update Games set winner = '" . $otherPlayer . "' where GameID is '" . $guid . "' and where winner is null");
                $up->execute();
                return $otherPlayer;
                
            }
        }
        
        //shouldn't get here
        return "messed up somewhere";
    }
    
    
    
    //gets other user for front end
    public function getOtherPlayer($username) {
        $check = $this->DB->prepare("select PlayerBlack from Games where PlayerRed is '" . $username . "' and where winner is null");
        $check->execute();
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select PlayerRed from Games where PlayerBlack is '" . $username . "' and where winner is null");
            $check2->execute();
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
        }
        return end($arr);
    }
}

?>