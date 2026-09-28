
<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "TaskManagement";

// Database connection
$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set character encoding
$conn->set_charset("utf8mb4");

?>