<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'food_order_db';

define("OPENROUTER_KEY", "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");

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

