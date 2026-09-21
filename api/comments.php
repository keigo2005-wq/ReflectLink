<?php

require_once __DIR__ . "/_bootstrap.php";

$currentUser = authenticateApiRequest($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse(["error" => "POSTメソッドのみ対応しています。"], 405);
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    jsonResponse(["error" => "リクエストボディはJSON形式で指定してください。"], 400);
}

$postId = (int)($input["post_id"] ?? 0);
$comment = trim($input["comment"] ?? "");

if ($postId <= 0 || $comment === "") {
    jsonResponse(["error" => "post_id と comment は必須です。"], 400);
}

$checkStmt = $pdo->prepare("SELECT id FROM soccer_posts WHERE id = :id");
$checkStmt->bindValue(":id", $postId, PDO::PARAM_INT);
$checkStmt->execute();

if (!$checkStmt->fetch()) {
    jsonResponse(["error" => "指定された投稿が見つかりません。"], 404);
}

$sql = "INSERT INTO comments (post_id, user_id, comment) VALUES (:post_id, :user_id, :comment)";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(":post_id", $postId, PDO::PARAM_INT);
$stmt->bindValue(":user_id", $currentUser["id"], PDO::PARAM_INT);
$stmt->bindValue(":comment", $comment);
$stmt->execute();

jsonResponse([
    "id" => (int)$pdo->lastInsertId(),
    "post_id" => $postId,
    "commenter_name" => $currentUser["name"],
    "position" => $currentUser["position"],
    "comment" => $comment,
], 201);
