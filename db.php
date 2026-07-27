<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$host = "localhost";
$user = "root";
$pass = "";
$dbname = "attendence"; 

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("<div style='color:red; font-family:sans-serif;'>Database Connection Failed: " . mysqli_connect_error() . "</div>");
}

mysqli_set_charset($conn, "utf8mb4");
?>