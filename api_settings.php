<?php

require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/auth.php";

$currentUser = requireLogin($pdo);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newKey = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("UPDATE users SET api_key = :api_key WHERE id = :id");
    $stmt->bindValue(":api_key", $newKey);
    $stmt->bindValue(":id", $currentUser["id"], PDO::PARAM_INT);
    $stmt->execute();

    header("Location: api_settings.php?generated=1");
    exit;
}

$stmt = $pdo->prepare("SELECT api_key FROM users WHERE id = :id");
$stmt->bindValue(":id", $currentUser["id"], PDO::PARAM_INT);
$stmt->execute();
$apiKey = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>APIキー設定</title>
</head>
<body>
<main class="container">
    <h1>APIキー設定</h1>

    <p class="current-user">
        ログイン中：<?= escape($currentUser["name"]) ?>
        ｜<a href="logout.php">ログアウト</a>
    </p>

    <?php if (isset($_GET["generated"]) && $_GET["generated"] === "1"): ?>
        <p class="success-message">新しいAPIキーを発行しました。</p>
    <?php endif; ?>

    <?php if ($apiKey): ?>
        <p>現在のAPIキー：</p>
        <p class="post-card" style="word-break: break-all;"><code><?= escape($apiKey) ?></code></p>
        <p>このキーを外部プログラムから使う場合は、リクエストヘッダーに以下を付ける。</p>
        <p class="post-card"><code>Authorization: Bearer <?= escape($apiKey) ?></code></p>
    <?php else: ?>
        <p>まだAPIキーが発行されていません。</p>
    <?php endif; ?>

    <form action="api_settings.php" method="post" onsubmit="return confirm('新しいAPIキーを発行すると、古いキーは使えなくなります。よろしいですか？');">
        <button type="submit"><?= $apiKey ? "APIキーを再発行する" : "APIキーを発行する" ?></button>
    </form>

    <a href="list.php">投稿一覧へ戻る</a>
</main>
</body>
</html>
