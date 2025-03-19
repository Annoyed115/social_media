<?php
// Basic DB connection settings:
$dbHost = "localhost";      // or your DB host
$dbName = "social_media";   // your database name
$dbUser = "root";           // your DB username
$dbPass = "";               // your DB password

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}

// Start a session to store user login state
session_start();
?>
