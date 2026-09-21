<?php

require_once "db.php";
require_once "functions.php";
require_once "auth.php";
require_once "action_plan_client.php";

$currentUser = requireLogin($pdo);

$postId = (int)($_GET["post_id"] ?? $_POST["post_id"] ?? 0);

if ($postId <= 0) {
    exit("投稿番号が正しくありません。");
}

$stmt = $pdo->prepare("SELECT id, match_name FROM soccer_posts WHERE id = :id");
$stmt->bindValue(":id", $postId, PDO::PARAM_INT);
$stmt->execute();
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    exit("指定された投稿が見つかりません。");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["update_status_id"])) {
            updateActionPlanStatus((int)$_POST["update_status_id"], $_POST["status"]);
        } elseif (isset($_POST["delete_id"])) {
            deleteActionPlan((int)$_POST["delete_id"]);
        } else {
            $action = trim($_POST["action"] ?? "");

            if ($action === "") {
                $error = "行動内容を入力してください。";
            } else {
                createActionPlan(
                    $postId,
                    $action,
                    trim($_POST["practice_menu"] ?? ""),
                    $_POST["due_date"] ?? "",
                    $currentUser["name"]
                );
            }
        }

        if ($error === "") {
            header("Location: action_plan.php?post_id=" . $postId);
            exit;
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$plans = [];
try {
    $plans = listActionPlans($postId);
} catch (RuntimeException $e) {
    $error = $error !== "" ? $error : $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>行動計画・練習メニュー</title>
</head>
<body>
<main class="container">
    <h1>行動計画・練習メニュー</h1>

    <p class="current-user">
        ログイン中：<?= escape($currentUser["name"]) ?>
        ｜<a href="logout.php">ログアウト</a>
    </p>

    <h2><?= escape($post["match_name"]) ?></h2>

    <?php if ($error !== ""): ?>
        <p class="error-message"><?= escape($error) ?></p>
    <?php endif; ?>

    <form action="action_plan.php" method="post" class="post-form">
        <input type="hidden" name="post_id" value="<?= (int)$postId ?>">

        <div class="form-group">
            <label for="action">次に取り組む行動<span class="required-mark">※必須</span></label>
            <textarea id="action" name="action" rows="3" maxlength="500"></textarea>
        </div>

        <div class="form-group">
            <label for="practice_menu">取り入れる練習メニュー(任意)</label>
            <textarea id="practice_menu" name="practice_menu" rows="3" maxlength="500"></textarea>
        </div>

        <div class="form-group">
            <label for="due_date">いつまでに(任意)</label>
            <input type="date" id="due_date" name="due_date">
        </div>

        <button type="submit">行動計画を追加する</button>
    </form>

    <?php if (empty($plans)): ?>
        <p>まだ行動計画がありません。</p>
    <?php else: ?>
        <?php foreach ($plans as $plan): ?>
            <section class="post-card">
                <p><strong><?= escape($plan["action"]) ?></strong></p>

                <?php if (!empty($plan["practiceMenu"])): ?>
                    <p>練習メニュー：<?= displayText($plan["practiceMenu"]) ?></p>
                <?php endif; ?>

                <?php if (!empty($plan["dueDate"])): ?>
                    <p>期限：<?= escape($plan["dueDate"]) ?></p>
                <?php endif; ?>

                <p>登録者：<?= escape($plan["createdByName"]) ?></p>

                <form action="action_plan.php" method="post" class="form-actions">
                    <input type="hidden" name="post_id" value="<?= (int)$postId ?>">
                    <input type="hidden" name="update_status_id" value="<?= (int)$plan["id"] ?>">
                    <select name="status" onchange="this.form.submit()">
                        <?php foreach (["未着手", "取り組み中", "完了"] as $statusOption): ?>
                            <option value="<?= escape($statusOption) ?>" <?= $plan["status"] === $statusOption ? "selected" : "" ?>>
                                <?= escape($statusOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <form action="action_plan.php" method="post" onsubmit="return confirm('この行動計画を削除してもよいですか？');">
                    <input type="hidden" name="post_id" value="<?= (int)$postId ?>">
                    <input type="hidden" name="delete_id" value="<?= (int)$plan["id"] ?>">
                    <button type="submit" class="action-button delete-button">削除する</button>
                </form>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="detail.php?id=<?= (int)$postId ?>">投稿詳細へ戻る</a>
</main>
</body>
</html>
