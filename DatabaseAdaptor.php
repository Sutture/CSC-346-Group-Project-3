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
        $check = $this->DB->prepare("select GameID from Games where PlayerBlack is null and where winner is null");
        $check->execute();
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        if(count($arr) > 0){
            $stmt = $this->DB->prepare("update Games set PlayerBlack = '" . $username . "' where GameID = '" . end($arr) . "'");
            $stmt->execute();
        } else {
            $board = encode_json(newGameBoard());
            $stmt = $this->DB->prepare("insert into Games (PlayerRed, Board) values ('" . $username . "' , '" . $board . "')");
            $stmt->execute();
            
        }
    }
    
    public function newGameBoard() {
        $game = array(
            array(0, 1, 0, 1, 0, 1, 0, 1),
            array(1, 0, 1, 0, 1, 0, 1, 0),
            array(0, 1, 0, 1, 0, 1, 0, 1),
            array(0, 0, 0, 0, 0, 0, 0, 0),
            array(0, 0, 0, 0, 0, 0, 0, 0),
            array(2, 0, 2, 0, 2, 0, 2, 0),
            array(0, 2, 0, 2, 0, 2, 0, 2),
            array(2, 0, 2, 0, 2, 0, 2, 0));
        return $game;
    }
    
    public function displayBoard($username) {
        $check = $this->DB->prepare("select Board from Games where PlayerRed is '" . $username . "' and where winner is null");
        $check->execute();
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select Board from Games where PlayerBlack is '" . $username . "' and where winner is null");
            $check2->execute();
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
        }
        
        if (count(arr) == 0) {
            $check = $this->DB->prepare("select Board from Games where PlayerRed is '" . $username . "' and where winner is not null");
            $check->execute();
            $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
            
        }
        if (count(arr) == 0) {
            $check = $this->DB->prepare("select Board from Games where PlayerBlack is '" . $username . "' and where winner is not null");
            $check->execute();
            $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
            
        }
        
        return end($arr);
    }
    
    public function move($oX, $oY, $mX, $mY, $username) {
        $pull = decode_json(displayBoard($username));
        
        //simple move
        if (( $oX - $mX == 1 || $oX - $mX == -1) && ($oY - $mY == 1 || $oY - $mY == -1)) {
            $pull[$mX][$mY] = $pull[$oX][$oY];
            $pull[$oX][$oY] = 0;
        }
        
        //jump move
        else {
            $pull[$mX][$mY] = $pull[$oX][$oY];
            $pull[($oX-$mX)/2][($oY-$mY)/2] = $pull[$oX][$oY];
            $pull[$oX][$oY] = 0;
        }
        
        $push = encode_json($pull);
        
        updateBoard($username, $push);
        
        return displayBoard($username);
    }
    
    public function updateBoard($username, $push) {
        
        $check = $this->DB->prepare("select GameID from Games where PlayerRed is '" . $username . "' and where winner is null");
        $check->execute();
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select GameID from Games where PlayerBlack is '" . $username . "' and where winner is null");
            $check2->execute();
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
        }
        
        $update = $this->DB->prepare("update Games set Board = '" . $push . "' where GameID = '" . end($arr) . "'");
        $update->execute();
    }
    
    //passes username of winner, null if not over
    public function isGameOver($username) {
        
        $check = $this->DB->prepare("select Board from Games where PlayerRed is '" . $username . "' and where winner is null");
        $check->execute();
        $arr = $check->fetchAll( PDO:: FETCH_ASSOC );
        $player = 0;
        if (count(arr) == 0) {
            $check2 = $this->DB->prepare("select Board from Games where PlayerBlack is '" . $username . "' and where winner is null");
            $check2->execute();
            $arr = $check2->fetchAll( PDO:: FETCH_ASSOC );
            $player = 2;
        }
        else {
            $player = 1;
        }
        
        $board = decode_json(end($arr));
        $other = 0;
        $player = 0;
        for($row = 0; $row < 8; $row++) {
            for($col = 0; $col < 8; $col++) {
                if($board[$row][$col] == $player) {
                    ++$player;
                }
                if($board[$row][$col] != $player && $board[$row][$col] != 0) {
                    ++$other;
                }
            }
        }
        $guid;
        if ($player == 1) {
            $up = $this->DB->prepare("select GameID from Games where PlayerRed is '" . $username . "' and where winner is null");
            $up->execute();
            $guid = end($up->fetchAll( PDO:: FETCH_ASSOC ));
            
        }
        if ($player == 2) {
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
                
            }
        }
        return end($arr);
        
        
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