<?php
// Database connection (PDO)
// Windows XAMPP default: host 127.0.0.1, user root, password empty, database mysihat
$host = "127.0.0.1";
$database_name = "mysihat";
$username = "root";
$password = "";
$charset = "utf8mb4";

$dsn = "mysql:host={$host};dbname={$database_name};charset={$charset}";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
  $pdo = new PDO($dsn, $username, $password, $options);
} catch (Exception $exception) {
  http_response_code(500);
  die("Database connection failed. Please check XAMPP MySQL and database settings.");
}
