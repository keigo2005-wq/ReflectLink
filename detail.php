<?php

require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/auth.php";

$currentUser = requireLogin($pdo);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postId = (int)($_POST["post_id"] ?? 0);
    $comment = trim($_POST["comment"] ?? "");

    if ($postId <= 0 || $comment === "") {
        $error = "コメントを入力してください。";
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

$sql = "SELECT soccer_posts.*, users.name AS player_name, categories.category_name
        FROM soccer_posts
        LEFT JOIN users ON soccer_posts.user_id = users.id
        LEFT JOIN categories ON soccer_posts.category_id = categories.id
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

$phaseClass = $post["phase"] === "攻撃" ? "phase-attack" : ($post["phase"] === "守備" ? "phase-defense" : "phase-transition");
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿詳細</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="container post-detail">
    <h1>投稿詳細</h1>

    <p class="current-user">
        ログイン中：<?= escape($currentUser["name"]) ?>
        ｜<a href="logout.php">ログアウト</a>
    </p>

    <section class="post-card detail-card">
        <div class="detail-card-header">
            <h2 class="detail-match-name"><?= escape($post["match_name"]) ?></h2>
            <div class="detail-tags">
                <span class="tag <?= $phaseClass ?>"><?= escape($post["phase"]) ?></span>
                <span class="tag tag-neutral"><?= escape($post["category_name"] ?? "未設定") ?></span>
            </div>
        </div>

        <?php if (!empty($post["image_name"])): ?>
            <div class="post-image">
                <img
                src="uploads/<?= escape(basename($post["image_name"])) ?>"
                alt="投稿されたプレー画像"
                >
            </div>
        <?php endif; ?>

        <div class="detail-field">
            <h3>発生した課題</h3>
            <p><?= displayText($post["issue"]) ?></p>
        </div>

        <div class="detail-field">
            <h3>原因</h3>
            <p><?= $post["cause"] !== "" ? displayText($post["cause"]) : "(未記入)" ?></p>
        </div>

        <div class="detail-field">
            <h3>改善案</h3>
            <p><?= $post["improvement"] !== "" ? displayText($post["improvement"]) : "(未記入)" ?></p>
        </div>

        <p class="detail-meta">投稿者：<?= escape($post["player_name"]) ?> ｜ 投稿日時：<?= escape($post["created_at"]) ?></p>
    </section>

    <section class="post-card comment-form-card">
        <h2>ポジション別のコメントを投稿する</h2>

        <?php if ($error !== ""): ?>
            <p class="error-message"><?= escape($error) ?></p>
        <?php endif; ?>

        <?php if (isset($_GET["commented"]) && $_GET["commented"] === "1"): ?>
            <p class="success-message">コメントを投稿しました。</p>
        <?php endif; ?>

        <form action="detail.php?id=<?= (int)$post["id"] ?>" method="post" class="post-form">
            <input type="hidden" name="post_id" value="<?= (int)$post["id"] ?>">

            <div class="form-group">
                <label for="comment">コメント(<?= escape($currentUser["name"]) ?> / <?= escape($currentUser["position"]) ?>として投稿されます)</label>
                <textarea id="comment" name="comment" rows="4" maxlength="500" required></textarea>
            </div>

            <button type="submit" class="button-primary">コメントを投稿する</button>
        </form>
    </section>

    <section class="post-card comment-list-card">
        <h2>投稿されたコメント</h2>

        <?php if (empty($comments)): ?>
            <p>まだコメントはありません。</p>
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

    <a href="list.php" class="button-secondary-link">投稿一覧へ戻る</a>
</main>

</body>
</html>
