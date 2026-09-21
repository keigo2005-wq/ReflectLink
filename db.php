<?php

if (file_exists(__DIR__ . "/db.local.php")) {
    require_once __DIR__ . "/db.local.php";
} else {
    $dsn = 'mysql:dbname=********;host=********;charset=utf8mb4';
    $user = '********';
    $password = '********';
}

$pdo = new PDO(
    $dsn,
    $user,
    $password,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING)
);
