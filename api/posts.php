<?php

require_once __DIR__ . "/_bootstrap.php";

$currentUser = authenticateApiRequest($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    jsonResponse(["error" => "GETメソッドのみ対応しています。"], 405);
}

$id = (int)($_GET["id"] ?? 0);

if ($id > 0) {
    $sql = "SELECT
                soccer_posts.id, soccer_posts.match_name, soccer_posts.match_date,
                soccer_posts.phase, categories.category_name, soccer_posts.issue,
                soccer_posts.cause, soccer_posts.improvement, soccer_posts.status,
                users.name AS player_name, soccer_posts.created_at
            FROM soccer_posts
            LEFT JOIN categories ON soccer_posts.category_id = categories.id
            LEFT JOIN users ON soccer_posts.user_id = users.id
            WHERE soccer_posts.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        jsonResponse(["error" => "指定された投稿が見つかりません。"], 404);
    }

    $commentSql = "
        SELECT comments.id, users.name AS commenter_name, users.position, comments.comment, comments.created_at
        FROM comments
        LEFT JOIN users ON comments.user_id = users.id
        WHERE comments.post_id = :post_id
        ORDER BY comments.created_at DESC
    ";
    $commentStmt = $pdo->prepare($commentSql);
    $commentStmt->bindValue(":post_id", $id, PDO::PARAM_INT);
    $commentStmt->execute();
    $post["comments"] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse($post);
}

$limit = min(max((int)($_GET["limit"] ?? 20), 1), 100);

$sql = "SELECT
            soccer_posts.id, soccer_posts.match_name, soccer_posts.match_date,
            soccer_posts.phase, categories.category_name, soccer_posts.status,
            users.name AS player_name, soccer_posts.created_at
        FROM soccer_posts
        LEFT JOIN categories ON soccer_posts.category_id = categories.id
        LEFT JOIN users ON soccer_posts.user_id = users.id
        ORDER BY soccer_posts.created_at DESC
        LIMIT :limit";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(["posts" => $posts, "count" => count($posts)]);
