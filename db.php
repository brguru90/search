<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname ="guru";
$conn = new mysqli($servername, $username, $password);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br />";
}
$conn->close();
?>