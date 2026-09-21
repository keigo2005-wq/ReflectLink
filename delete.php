<?php
require_once("includes/db.php");
require_once("includes/auth.php");

$currentUser = requireLogin($pdo);

// POST送信以外では処理しない
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("不正なアクセスです。");
}

// 削除対象の投稿番号を受け取る
$id = (int)($_POST["id"] ?? 0);

if ($id <= 0) {
    exit("投稿番号が正しくありません。");
}

// 所有者以外による削除を防ぐ
$ownerStmt = $pdo->prepare("SELECT user_id FROM soccer_posts WHERE id = :id");
$ownerStmt->bindValue(":id", $id, PDO::PARAM_INT);
$ownerStmt->execute();
$owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

if (!$owner) {
    exit("指定された投稿が見つかりません。");
}

if ((int)$owner["user_id"] !== (int)$currentUser["id"]) {
    exit("この投稿を削除する権限がありません。");
}

// 投稿を削除する
$sql = "DELETE FROM soccer_posts WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $id, PDO::PARAM_INT);
$stmt->execute();

// 一覧画面へ戻る
header("Location: list.php?deleted=1");
exit;