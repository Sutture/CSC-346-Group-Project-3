<?php

session_start();
require_once "DatabaseAdaptor.php";
$databaseAdaptor = new DatabaseAdaptor();


if (isset($_POST["method"])){
    $method = $_POST["method"];
}

//user login method

//manages user login attemps
if ($method == "login"){
    if (isset($_POST["username"])){
        $username = $_POST["username"];
    }
    if (isset($_POST["password"])){
        $password = $_POST["password"];
    }
    if (isset($_POST["username"]) && isset($_POST["password"])){
        if ($databaseAdaptor->checkUserCredentials($username, $password) == TRUE){
            $_SESSION["user"] = $username;
            echo true;
        }
        else{
            echo false;
        }
    }
}

//manages user registrations
if ($method == "registration"){
    if (isset($_POST["username"])){
        $username = $_POST["username"];
    }
    if (isset($_POST["password"])){
        $password = $_POST["password"];
    }
    
    $arr = $databaseAdaptor->checkForUsername($username);
    if (count($arr) > 0){
        echo "Userame taken, try a different username";
    }
    else{
        $databaseAdaptor->insertNewUser($username, $password);
        echo 'Registration succesful';
    }
}
if ($method == "displayBoard"){
    if (isset($_SESSION["user"])){
        echo $databaseAdaptor->displayBoard($_SESSION["user"]);   
    }
}

if ($method == "getOtherPlayer"){
    if (isset($_SESSION["user"])){
        echo $databaseAdaptor->getOtherPlayer($_SESSION["user"]);
    }
}

if ($method == "searchGames"){
    if (isset($_SESSION["user"])){
        $databaseAdaptor->searchGames($_SESSION["user"]);
    }
}

if ($method == "insertGame"){
    if (isset($_SESSION["user"])){
        echo $databaseAdaptor->insertGame($_SESSION["user"]);
    }
}





?>

