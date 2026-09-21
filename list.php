<?php
//DBに接続
require_once("includes/db.php");
require_once "includes/functions.php";
require_once "includes/auth.php";

$currentUser = requireLogin($pdo);

$categorySql = "SELECT id, category_name FROM categories ORDER BY id";
$categoryStmt = $pdo->query($categorySql);
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedCategory = $_GET["category_id"] ?? "";
$selectedStatus = $_GET["status"] ?? "";

$sql = "SELECT
            soccer_posts.*,
            categories.category_name,
            users.name AS player_name
        FROM soccer_posts
        LEFT JOIN categories
            ON soccer_posts.category_id = categories.id
        LEFT JOIN users
            ON soccer_posts.user_id = users.id";

$conditions = [];
$params = [];

if ($selectedCategory !== "") {
    $conditions[] = "soccer_posts.category_id = :category_id";
    $params[":category_id"] = (int)$selectedCategory;
}

if ($selectedStatus !== "") {
    $conditions[] = "soccer_posts.status = :status";
    $params[":status"] = $selectedStatus;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY soccer_posts.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function phaseClass(string $phase): string
{
    if ($phase === "攻撃") return "text-attack";
    if ($phase === "守備") return "text-defense";
    return "text-transition";
}

function statusClass(string $status): string
{
    if ($status === "未実施") return "text-status-todo";
    if ($status === "実践中") return "text-status-doing";
    return "text-status-done";
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>投稿一覧</title>
</head>
<body>
    <div class="container">
    <h1>投稿一覧</h1>

    <p class="current-user">
        ログイン中：<?php echo htmlspecialchars($currentUser["name"], ENT_QUOTES, "UTF-8"); ?>
        ｜<a href="logout.php">ログアウト</a>
    </p>

    <form method="get" action="list.php" class="search-form">
        <div class="form-group">
            <label for="category_id">カテゴリー</label>
            <select name="category_id" id="category_id">
                <option value="">すべて</option>

                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category["id"]; ?>"
                        <?php if ((string)$selectedCategory === (string)$category["id"]) echo "selected"; ?>
                    >
                        <?php echo htmlspecialchars($category["category_name"], ENT_QUOTES, "UTF-8"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="status">改善状況</label>
            <select name="status" id="status">
                <option value="">すべて</option>
                <option value="未実施" <?php if ($selectedStatus === "未実施") echo "selected"; ?>>未実施</option>
                <option value="実践中" <?php if ($selectedStatus === "実践中") echo "selected"; ?>>実践中</option>
                <option value="達成済み" <?php if ($selectedStatus === "達成済み") echo "selected"; ?>>達成済み</option>
            </select>
        </div>

        <button type="submit">絞り込む</button>
        <a href="list.php">条件を解除</a>
    </form>

    <p class="search-result-count">検索結果：<?php echo count($posts); ?>件</p>

    <?php if (isset($_GET["updated"]) && $_GET["updated"] === "1"): ?>
        <p class="success-message">投稿を更新しました。</p>
    <?php endif; ?>

    <?php if (isset($_GET["deleted"]) && $_GET["deleted"] === "1"): ?>
        <p class="success-message">投稿を削除しました。</p>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <p>まだ投稿はありません。</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <h2 class="post-match-name">
                    <?php echo htmlspecialchars($post["match_name"], ENT_QUOTES, "UTF-8"); ?>
                </h2>

                <p>
                    試合日：
                    <?php echo htmlspecialchars($post["match_date"], ENT_QUOTES, "UTF-8"); ?>
                </p>

                <p>
                    局面：
                    <strong class="<?= phaseClass($post["phase"]) ?>">
                        <?php echo htmlspecialchars($post["phase"], ENT_QUOTES, "UTF-8"); ?>
                    </strong>
                </p>

                <p>
                    課題カテゴリー：
                    <strong><?php echo htmlspecialchars($post["category_name"] ?? "未設定", ENT_QUOTES, "UTF-8");?></strong>
                </p>

                <p>
                    発生した課題：
                    <?php echo displayText($post["issue"]); ?>
                </p>

                <p>
                    原因：
                    <?php echo $post["cause"] !== "" ? displayText($post["cause"]) : "(未記入)"; ?>
                </p>

                <p>
                    改善案：
                    <?php echo $post["improvement"] !== "" ? displayText($post["improvement"]) : "(未記入)"; ?>
                </p>

                <p>
                    改善状況：
                    <strong class="<?= statusClass($post["status"]) ?>">
                        <?php echo htmlspecialchars($post["status"], ENT_QUOTES, "UTF-8");?>
                    </strong>
                </p>

                <p>
                    投稿者名：
                    <?php echo htmlspecialchars($post["player_name"], ENT_QUOTES, "UTF-8"); ?>
                </p>

                <p>
                    投稿日時：
                    <?php echo htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8"); ?>
                </p>
            </div>

            <div class="post-actions">
                <a
                    href="detail.php?id=<?= (int)$post["id"] ?>"
                    class="action-button detail-button"
                >
                    詳細を見る
                </a>

                <?php if ((int)$post["user_id"] === (int)$currentUser["id"]): ?>
                    <a
                        href="edit.php?id=<?php echo (int)$post["id"]; ?>"
                        class="action-button edit-button"
                    >
                        編集する
                    </a>

                    <form
                        action="delete.php" method="post" class="delete-form"
                        onsubmit="return confirm('この投稿を削除してもよいですか？');"
                    >

                        <input type="hidden" name="id" value="<?php echo (int)$post["id"]; ?>">

                        <button type="submit" class="action-button delete-button">
                            削除する
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>

        <div class="list-actions">
            <a href="post.php" class="list-actions-primary">新規投稿</a>
            <a href="goals.php">目標管理</a>
            <a href="analysis.php">傾向分析</a>
            <a href="statistics.php">統計</a>
            <a href="export_csv.php">データダウンロード</a>
        </div>
    </div>
</body>
</html>
