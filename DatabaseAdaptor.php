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
    
    public function searchGames($username){
        $check = $this->DB->prepare("select GameID from Games where PlayerBlack is null");
        $check->execute();
        $arr = $check->fetchAll(PDO:: FETCH_ASSOC);
        if(count($arr) > 0){
            $stmt = $this->DB->prepare("update Games set PlayerBlack = '" . $username . "' where GameID = '" . end($arr) . "'");
            $stmt->execute();
        } else{
            $stmt = $this->DB->prepare('insert into games (PlayerRed, Board) values ("' . $username . '", "' . $board . '")');
            $stmt->execute();
        }
    }

    public function insertGame($username){
        $board = json_encode($this->newGameBoard());
        $stmt = $this->DB->prepare('insert into games (PlayerRed, Board) values ("' . $username . '",' . $board . ')');
        $stmt->execute();
        return "inserted new game";
    }
    
    public function newGameBoard($PlayerRed) {
        $redRight = "01010101";
        $redLeft = "10101010";
        $mid = "00000000";
        $blackLeft = "20202020";
        $blackRight = "02020202";
        
        $stmt = $this->DB->prepare('insert into games (PlayerRed, Row0, Row1, Row2, Row3, Row4, Row5, Row6, Row7) 
            values (' . $PlayerRed . ',' . $redRight .  ',' . $redLeft . ',' . $redRight .  ',' . $mid . ',' . $mid . ',' . $blackLeft . ',' . $blackRight . ',' . $blackLeft . ')');
        $stmt->execute();
        
    }
    
    public function phpBoard($GameID) {
        
    }
    
    public function displayBoard($username) {
        $check = $this->DB->prepare("select GameID from Games where PlayerRed is '" . $username . "' and winner is null");
        $check->execute();
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        if (count($arr) == 0) {
            $check2 = $this->DB->prepare('select GameID from Games where PlayerBlack is "' . $username . '" and winner is null');
            $check2->execute();
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
        }
        
        if (count($arr) == 0) {
            $check = $this->DB->prepare("select GameID from Games where (PlayerRed is '" . $username . "' or PlayerBlack is '" . $username . "') and winner is not null");
            $check->execute();
            $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
            
        }
        
        $curr = end($arr);
        $return = array();
        $check = $this->DB->prepare("select row0, row1, row2, row3, row4, row5, row6, row7 from Games where GameID is '" . $curr . "'");
        $check->execute();
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
    
    //TODO fix
    public function move($oX, $oY, $mX, $mY, $username) {
        $pull = json_decode(displayBoard($username));
        
        //simple move
        if (( $oX - $mX == 1 || $oX - $mX == -1) && ($oY - $mY == 1 || $oY - $mY == -1)) {
            $pull[$mX][$mY] = $pull[$oX][$oY];
            $pull[$oX][$oY] = "0";
        }
        
        //jump move
        else {
            $pull[$mX][$mY] = $pull[$oX][$oY];
            $pull[($oX-$mX)/2][($oY-$mY)/2] = $pull[$oX][$oY];
            $pull[$oX][$oY] = "0";
        }
        
        $push = json_encode($pull);
        
        updateBoard($username, $push);
        
        return displayBoard($username);
    }
    
    //TODO fix
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
        
        $check = $this->DB->prepare("select Board from Games where PlayerRed is '" . $username . "' and winner is null");
        $check->execute();
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        $player = 0;
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select Board from Games where PlayerBlack is '" . $username . "' and winner is null");
            $check2->execute();
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
            $player = "2";
        }
        else {
            $player = "1";
        }
        
        $board = json_decode(end($arr));
        $other = 0;
        $playerScore = 0;
        for($row = 0; $row < 8; $row++) {
            for($col = 0; $col < 8; $col++) {
                if($board[$row][$col] == $player) {
                    ++$playerScore;
                }
                if($board[$row][$col] != $player && $board[$row][$col] != "0") {
                    ++$other;
                }
            }
        }
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
        
        if ($other != 0 && $player != 0) {
            return null;
        }
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
        return "messed up somewhere";
    }
    
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