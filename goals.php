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
                $_POST["period_start"] ?? "",
                $_POST["period_end"] ?? "",
                trim($_POST["goal"] ?? ""),
                trim($_POST["action"] ?? ""),
                trim($_POST["result"] ?? ""),
                $_POST["status"] ?? "未着手"
            );
        } else {
            $periodStart = $_POST["period_start"] ?? "";
            $periodEnd = $_POST["period_end"] ?? "";
            $goal = trim($_POST["goal"] ?? "");
            $action = trim($_POST["action"] ?? "");

            if ($periodStart === "" || $periodEnd === "") {
                $error = "対象期間(開始日・終了日)を入力してください。";
            } elseif ($goal === "" || $action === "") {
                $error = "目標と取り組む行動を入力してください。";
            } else {
                createGoal($currentUser["id"], $periodStart, $periodEnd, $goal, $action);
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

// 次に迎える日曜日を新規フォームの開始日の初期値にする(今日が日曜ならその日)
$today = new DateTime("today");
$daysUntilSunday = (7 - (int)$today->format("w")) % 7;
$nextSunday = (clone $today)->modify("+{$daysUntilSunday} days");
$followingSaturday = (clone $nextSunday)->modify("+6 days");

function statusClass(string $status): string
{
    if ($status === "完了") return "goal-status-done";
    if ($status === "取り組み中") return "goal-status-doing";
    return "goal-status-todo";
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
    <p class="page-lead">
        毎週日曜日のミーティングや個人での振り返りをもとに、次の1週間の目標と行動を記録する個人用のページです（他の部員には表示されません）。
        前の週の目標には、振り返って「取り組んだ結果（自己評価）」を書き足してください。
    </p>

    <p class="current-user">
        ログイン中：<?= escape($currentUser["name"]) ?>
        ｜<a href="logout.php">ログアウト</a>
    </p>

    <?php if ($error !== ""): ?>
        <p class="error-message"><?= escape($error) ?></p>
    <?php endif; ?>

    <section class="post-form goal-new-form">
        <h2>新しい週の目標を追加する</h2>
        <form action="goals.php" method="post">
            <div class="form-group goal-period-group">
                <label for="period_start">対象期間<span class="required-mark">※必須</span></label>
                <div class="goal-period-inputs">
                    <input type="date" id="period_start" name="period_start" value="<?= $nextSunday->format("Y-m-d") ?>">
                    <span>〜</span>
                    <input type="date" name="period_end" value="<?= $followingSaturday->format("Y-m-d") ?>">
                </div>
            </div>

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

                    <div class="form-group goal-period-group">
                        <label>対象期間</label>
                        <div class="goal-period-inputs">
                            <input type="date" name="period_start" value="<?= escape($g["periodStart"]) ?>">
                            <span>〜</span>
                            <input type="date" name="period_end" value="<?= escape($g["periodEnd"]) ?>">
                        </div>
                    </div>

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
                        <select name="status" class="goal-status-select <?= statusClass($g["status"]) ?>">
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
