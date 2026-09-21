<?php

require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/auth.php";
require_once "includes/goals_client.php";

$currentUser = requireLogin($pdo);

$error = "";
$statuses = ["未着手", "取り組み中", "完了"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST["delete_id"])) {
            deleteGoal((int)$_POST["delete_id"]);
        } elseif (isset($_POST["update_id"])) {
            updateGoal(
                (int)$_POST["update_id"],
                trim($_POST["goal"] ?? ""),
                trim($_POST["action"] ?? ""),
                trim($_POST["result"] ?? ""),
                $_POST["status"] ?? "未着手"
            );
        } else {
            $goal = trim($_POST["goal"] ?? "");
            $action = trim($_POST["action"] ?? "");

            if ($goal === "" || $action === "") {
                $error = "目標と取り組む行動を入力してください。";
            } else {
                createGoal($currentUser["id"], $goal, $action);
            }
        }

        if ($error === "") {
            header("Location: goals.php");
            exit;
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$goals = [];
try {
    $goals = listGoals($currentUser["id"]);
} catch (RuntimeException $e) {
    $error = $error !== "" ? $error : $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>目標管理</title>
</head>
<body>
<main class="container">
    <h1>目標管理</h1>
    <p class="page-lead">試合や練習、ミーティングを通して感じたことをもとに、自分自身の目標と行動を記録する個人用のページです（他の部員には表示されません）。</p>

    <p class="current-user">
        ログイン中：<?= escape($currentUser["name"]) ?>
        ｜<a href="logout.php">ログアウト</a>
    </p>

    <?php if ($error !== ""): ?>
        <p class="error-message"><?= escape($error) ?></p>
    <?php endif; ?>

    <section class="post-form goal-new-form">
        <h2>新しい目標を追加する</h2>
        <form action="goals.php" method="post">
            <div class="form-group">
                <label for="goal">具体的な目標<span class="required-mark">※必須</span></label>
                <textarea id="goal" name="goal" rows="2" maxlength="500"></textarea>
            </div>

            <div class="form-group">
                <label for="action">目標のために取り組む行動<span class="required-mark">※必須</span></label>
                <textarea id="action" name="action" rows="2" maxlength="500"></textarea>
            </div>

            <button type="submit" class="button-primary">目標を追加する</button>
        </form>
    </section>

    <?php if (empty($goals)): ?>
        <p>まだ目標が登録されていません。</p>
    <?php else: ?>
        <?php foreach ($goals as $g): ?>
            <section class="post-card goal-card">
                <form action="goals.php" method="post">
                    <input type="hidden" name="update_id" value="<?= (int)$g["id"] ?>">

                    <div class="form-group">
                        <label>具体的な目標</label>
                        <textarea name="goal" rows="2" maxlength="500"><?= escape($g["goal"]) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>取り組む行動</label>
                        <textarea name="action" rows="2" maxlength="500"><?= escape($g["action"]) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>取り組んだ結果（自己評価）</label>
                        <textarea name="result" rows="2" maxlength="500" placeholder="振り返って感じたことを書く"><?= escape($g["result"] ?? "") ?></textarea>
                    </div>

                    <div class="goal-card-footer">
                        <select name="status" class="goal-status-select goal-status-<?= $g["status"] === "完了" ? "done" : ($g["status"] === "取り組み中" ? "doing" : "todo") ?>">
                            <?php foreach ($statuses as $statusOption): ?>
                                <option value="<?= escape($statusOption) ?>" <?= $g["status"] === $statusOption ? "selected" : "" ?>>
                                    <?= escape($statusOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="button-primary">更新する</button>
                    </div>
                </form>

                <form action="goals.php" method="post" class="goal-delete-form" onsubmit="return confirm('この目標を削除してもよいですか？');">
                    <input type="hidden" name="delete_id" value="<?= (int)$g["id"] ?>">
                    <button type="submit" class="action-button delete-button">削除する</button>
                </form>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="list.php" class="button-secondary-link">投稿一覧へ戻る</a>
</main>
</body>
</html>
