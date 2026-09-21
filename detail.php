<?php

require_once "db.php";
require_once "functions.php";
require_once "auth.php";

$currentUser = requireLogin($pdo);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postId = (int)($_POST["post_id"] ?? 0);
    $comment = trim($_POST["comment"] ?? "");

    if ($postId <= 0 || $comment === "") {
        $error = "意見を入力してください。";
    } else {
        $sql = "
            INSERT INTO comments
                (post_id, user_id, comment)
            VALUES
                (:post_id, :user_id, :comment)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":post_id", $postId, PDO::PARAM_INT);
        $stmt->bindValue(":user_id", $currentUser["id"], PDO::PARAM_INT);
        $stmt->bindValue(":comment", $comment);
        $stmt->execute();

        header("Location: detail.php?id=" . $postId . "&commented=1");
        exit;
    }
}

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    exit("投稿番号が正しくありません。");
}

$sql = "SELECT soccer_posts.*, users.name AS player_name
        FROM soccer_posts
        LEFT JOIN users ON soccer_posts.user_id = users.id
        WHERE soccer_posts.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $id, PDO::PARAM_INT);
$stmt->execute();

$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    exit("指定された投稿は見つかりませんでした。");
}

$commentSql = "
    SELECT
        comments.id,
        users.position,
        users.name AS commenter_name,
        comments.comment,
        comments.created_at
    FROM comments
    LEFT JOIN users ON comments.user_id = users.id
    WHERE comments.post_id = :post_id
    ORDER BY comments.created_at DESC, comments.id DESC
";

$commentStmt = $pdo->prepare($commentSql);
$commentStmt->bindValue(":post_id", $id, PDO::PARAM_INT);
$commentStmt->execute();

$comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿詳細</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="post-detail">
    <h1>投稿詳細</h1>

    <h2><?= escape($post["match_name"]) ?></h2>
    
    <?php if (!empty($post["image_name"])): ?>
　      <div class="post-image">
            <img
            src="uploads/<?= escape(basename($post["image_name"])) ?>"
            alt="投稿されたプレー画像"
            >
        </div>
　　<?php endif; ?>

    <p>
        発生した課題：<br>
        <?= displayText($post["issue"]) ?>
    </p>

    <p>
        原因：<br>
        <?= displayText($post["cause"]) ?>
    </p>

    <p>
        改善案：<br>
        <?= displayText($post["improvement"]) ?>
    </p>

    <p>
        <a href="action_plan.php?post_id=<?= (int)$post["id"] ?>">行動計画・練習メニューを見る</a>
    </p>

　　<section class="comment-section">
        <h2>ポジション別の意見</h2>

        <?php if ($error !== ""): ?>
            <p class="error-message"><?= escape($error) ?></p>
        <?php endif; ?>

        <?php if (isset($_GET["commented"]) && $_GET["commented"] === "1"): ?>
            <p class="success-message">
                意見を投稿しました。
            </p>
        <?php endif; ?>

        <p class="current-user">
            コメント者：<?= escape($currentUser["name"]) ?>(<?= escape($currentUser["position"]) ?>)
            ｜<a href="logout.php">ログアウト</a>
        </p>

        <form action="detail.php?id=<?= (int)$post["id"] ?>" method="post">
            <input type="hidden" name="post_id" value="<?= (int)$post["id"] ?>">

            <div>
                <label for="comment">意見</label>
                <textarea id="comment" name="comment" maxlength="500" required></textarea>
            </div>

            <button type="submit">意見を投稿する</button>
        </form>
　　</section>
　　
　　<section class="comment-list">
    <h2>投稿された意見</h2>

    <?php if (empty($comments)): ?>
        <p>まだ意見はありません。</p>
    <?php else: ?>
        <?php foreach ($comments as $commentData): ?>
            <article class="comment-card">
                <p class="comment-info">
                    <span class="position-label">
                        <?= escape($commentData["position"]) ?>
                    </span>

                    <strong>
                        <?= escape($commentData["commenter_name"]) ?>
                    </strong>

                    <time>
                        <?= escape($commentData["created_at"]) ?>
                    </time>
                </p>

                <p class="comment-text">
                    <?= displayText($commentData["comment"]) ?>
                </p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

    <a href="list.php">投稿一覧へ戻る</a>
</main>

</body>
</html>