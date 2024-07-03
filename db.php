<?php
$servername = "localhost";
$username = "root";  // Default xampp ime
$password = "";  // Default xampp lozinka
$dbname = "ebanking";  // database ime


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
