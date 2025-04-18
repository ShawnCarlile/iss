<?php
$host = 'localhost'; // Change if your database is hosted elsewhere
$dbname = 'iss'; // Replace with your actual database name
$username = 'root'; // Replace with your actual DB username
$password = ''; // Replace with your actual DB password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>