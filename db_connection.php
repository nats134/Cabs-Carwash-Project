<?php
// Database connection settings
$host = "localhost";  // Change if your database is hosted elsewhere
$user = "root";       // Change to your MySQL username
$password = "";       // Change to your MySQL password
$database = "carwash_db"; // Change to your actual database name

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
