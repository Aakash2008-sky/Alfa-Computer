<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "alfa_computer";


$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);


// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}


// Character set
$conn->set_charset("utf8mb4");

?>
