<?php

require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/auth.php";

$currentUser = requireLogin($pdo);

$sql = "
    SELECT users.position AS position, COUNT(*) AS comment_count
    FROM comments
    LEFT JOIN users ON comments.user_id = users.id
    GROUP BY users.position
    ORDER BY comment_count DESC
";

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$positionLabels = [];
$commentCounts = [];
$totalComments = 0;
$topPosition = null;

foreach ($results as $result) {
    $positionLabels[] = $result["position"];
    $commentCounts[] = (int)$result["comment_count"];
    $totalComments += (int)$result["comment_count"];
    if ($topPosition === null) {
        $topPosition = $result["position"];
    }
}

$statusSql = "
    SELECT status, COUNT(*) AS post_count
    FROM soccer_posts
    GROUP BY status
";
$statusStmt = $pdo->query($statusSql);
$statusResults = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

$statusOrder = ["未実施", "実践中", "達成済み"];
$statusCounts = array_fill_keys($statusOrder, 0);
foreach ($statusResults as $row) {
    if (isset($statusCounts[$row["status"]])) {
        $statusCounts[$row["status"]] = (int)$row["post_count"];
    }
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>統計</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="container">
    <h1>統計</h1>
    <p class="page-lead">ポジション別のコメント状況と、投稿の改善状況をまとめて確認できます。</p>

    <p class="current-user">
        ログイン中：<?= escape($currentUser["name"]) ?>
        ｜<a href="logout.php">ログアウト</a>
    </p>

    <?php if (empty($results)): ?>
        <p>集計できるコメントがありません。</p>
    <?php else: ?>
        <div class="stat-tiles">
            <div class="stat-tile">
                <span class="stat-tile-value"><?= $totalComments ?></span>
                <span class="stat-tile-label">総コメント数</span>
            </div>
            <div class="stat-tile">
                <span class="stat-tile-value"><?= escape($topPosition) ?></span>
                <span class="stat-tile-label">最も活発なポジション</span>
            </div>
        </div>

        <section class="post-card">
            <h2>ポジション別コメント件数</h2>
            <div class="chart-container">
                <canvas id="positionChart"></canvas>
            </div>
        </section>
    <?php endif; ?>

    <section class="post-card">
        <h2>改善状況の内訳</h2>
        <div class="chart-container">
            <canvas id="statusChart"></canvas>
        </div>
    </section>

    <a href="list.php" class="button-secondary-link">投稿一覧へ戻る</a>
</main>

<script>
    window.positionChartData = {
        labels: <?= json_encode($positionLabels, JSON_UNESCAPED_UNICODE) ?>,
        counts: <?= json_encode($commentCounts) ?>
    };

    window.statusChartData = {
        labels: <?= json_encode(array_keys($statusCounts), JSON_UNESCAPED_UNICODE) ?>,
        counts: <?= json_encode(array_values($statusCounts)) ?>
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script src="statistics.js"></script>

</body>
</html>
