<?php

require_once __DIR__ . "/../includes/db.php";

header("Content-Type: application/json; charset=UTF-8");

function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function authenticateApiRequest(PDO $pdo): array
{
    $header = $_SERVER["HTTP_AUTHORIZATION"] ?? "";

    if (!preg_match('/^Bearer\s+(\S+)$/', $header, $matches)) {
        jsonResponse(["error" => "Authorizationヘッダーに 'Bearer <APIキー>' を指定してください。"], 401);
    }

    $apiKey = $matches[1];

    $stmt = $pdo->prepare("SELECT id, name, position FROM users WHERE api_key = :api_key");
    $stmt->bindValue(":api_key", $apiKey);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(["error" => "APIキーが無効です。"], 401);
    }

    return $user;
}
