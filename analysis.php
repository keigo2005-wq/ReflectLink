<?php

require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/auth.php";
require_once "includes/gemini.php";

$currentUser = requireLogin($pdo);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postSql = "
        SELECT
            soccer_posts.match_name,
            soccer_posts.phase,
            categories.category_name,
            soccer_posts.issue,
            soccer_posts.cause,
            soccer_posts.improvement,
            soccer_posts.status
        FROM soccer_posts
        LEFT JOIN categories ON soccer_posts.category_id = categories.id
        ORDER BY soccer_posts.created_at DESC
        LIMIT 50
    ";
    $posts = $pdo->query($postSql)->fetchAll(PDO::FETCH_ASSOC);

    if (empty($posts)) {
        $error = "分析対象の投稿がまだありません。";
    } else {
        $lines = [];
        foreach ($posts as $i => $post) {
            $lines[] = ($i + 1) . "件目 [局面:{$post['phase']} / カテゴリー:{$post['category_name']} / 状況:{$post['status']}]\n"
                . "課題: {$post['issue']}\n原因: {$post['cause']}\n改善案: {$post['improvement']}";
        }

        $prompt = "あなたはサッカーチームのデータ分析アシスタントです。\n"
            . "以下は部員が投稿した「試合・練習の振り返り」の一覧です(新しい順)。\n"
            . "これらを分析し、次の3点を日本語で簡潔にまとめてください。\n"
            . "1. 繰り返し発生している課題のパターン\n"
            . "2. 局面(攻撃・守備・攻守の切り替え)別の傾向\n"
            . "3. 過去の知見を組み合わせて考えられる、新しい改善アイデア\n\n"
            . "---\n"
            . implode("\n\n", $lines);

        try {
            $summary = trim(callGeminiApi($prompt));

            $insertSql = "INSERT INTO ai_analyses (summary, post_count, generated_by)
                          VALUES (:summary, :post_count, :generated_by)";
            $stmt = $pdo->prepare($insertSql);
            $stmt->bindValue(":summary", $summary);
            $stmt->bindValue(":post_count", count($posts), PDO::PARAM_INT);
            $stmt->bindValue(":generated_by", $currentUser["id"], PDO::PARAM_INT);
            $stmt->execute();

            header("Location: analysis.php?generated=1");
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$latestSql = "
    SELECT ai_analyses.*, users.name AS generated_by_name
    FROM ai_analyses
    LEFT JOIN users ON ai_analyses.generated_by = users.id
    ORDER BY ai_analyses.created_at DESC
    LIMIT 1
";
$latest = $pdo->query($latestSql)->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>AIによる傾向分析</title>
</head>
<body>
<main class="container">
    <h1>AIによる傾向分析</h1>

    <p class="current-user">
        ログイン中：<?= escape($currentUser["name"]) ?>
        ｜<a href="logout.php">ログアウト</a>
    </p>

    <?php if ($error !== ""): ?>
        <p class="error-message"><?= escape($error) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET["generated"]) && $_GET["generated"] === "1"): ?>
        <p class="success-message">分析結果を更新しました。</p>
    <?php endif; ?>

    <form action="analysis.php" method="post" class="analyze-form">
        <button type="submit" class="button-primary button-large">AIで最新の傾向を分析する</button>
    </form>

    <?php if ($latest): ?>
        <section class="post-card">
            <p>
                分析日時：<?= escape($latest["created_at"]) ?>
                (対象<?= (int)$latest["post_count"] ?>件 / 実行者：<?= escape($latest["generated_by_name"]) ?>)
            </p>
            <p class="comment-text">
                <?= displayText($latest["summary"]) ?>
            </p>
        </section>
    <?php else: ?>
        <p>まだ分析結果がありません。上のボタンから実行してください。</p>
    <?php endif; ?>

    <a href="list.php" class="button-secondary-link">投稿一覧へ戻る</a>
</main>
</body>
</html>
