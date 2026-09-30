<?php
// public_html/config/db.php
// Singleton PDO connection — require_once and use $pdo = require __DIR__ . '/../config/db.php';

$host    = '127.0.0.1';
$db      = 'nhmrd';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

return $pdo;