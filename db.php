<?php
$host = "127.0.0.1";
$user = "root";
$password = "123456";
$database = "vinyl_crate";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error . "<br>Please run <a href='db_rebuild.php'>db_rebuild.php</a> first.");
}

$conn->set_charset("utf8mb4");
?>
