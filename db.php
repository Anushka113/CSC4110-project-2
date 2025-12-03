<?php
// Database connection file

$host = 'localhost';
$db   = 'cleaning_db';
$user = 'root';
$pass = '';  // if you set a MySQL password, put it here

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
