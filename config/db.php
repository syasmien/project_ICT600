<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "event_hall_booking";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: success message (boleh buang kalau tak nak nampak)
// echo "Connected successfully";
?>