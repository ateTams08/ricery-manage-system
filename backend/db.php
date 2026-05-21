<?php
$user="root";
$password="sqlaCc0906_";
$database = "project";
$servername= "localhost:3310";

$mysqli= new mysqli($servername, $user, $password, $database);

if ($mysqli->connect_errno) {
    die("Connection failed: " . $mysqli->connect_error);
}

?>