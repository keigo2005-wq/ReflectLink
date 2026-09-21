<?php
require_once("includes/db.php");
require_once("includes/auth.php");

$currentUser = requireLogin($pdo);

$allowedStatuses = ["未実施", "実践中", "達成済み"];

function assertOwnership(PDO $pdo, int $id, int $userId): void
{
    $stmt = $pdo->prepare("SELECT user_id FROM soccer_posts WHERE id = :id");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        exit("指定された投稿が見つかりません。");
    }

    if ((int)$post["user_id"] !== $userId) {
        exit("この投稿を編集する権限がありません。");
    }
}

// 更新ボタンが押された場合
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = (int)($_POST["id"] ?? 0);
    $status = $_POST["status"] ?? "";
    $issue = trim($_POST["issue"] ?? "");
    $cause = trim($_POST["cause"] ?? "");
    $improvement = trim($_POST["improvement"] ?? "");
    $matchName = trim($_POST["match_name"] ?? "");
    $matchDate = $_POST["match_date"] ?? "";
    $phase = $_POST["phase"] ?? "";
    $categoryId = (int)($_POST["category_id"] ?? 0);

    if ($id <= 0) {
        exit("投稿番号が正しくありません。");
    }

    assertOwnership($pdo, $id, (int)$currentUser["id"]);

    if ($matchName === "") {
        exit("試合名・対戦相手を入力してください。");
    }

    if ($matchDate === "") {
        exit("試合日を入力してください。");
    }

    $allowedPhases = ["攻撃", "守備", "攻守の切り替え"];

    if (!in_array($phase, $allowedPhases, true)) {
        exit("局面が正しくありません。");
    }
    
    if ($categoryId <= 0) {
        exit("課題カテゴリーを選択してください。");
    }


    if ($issue === "") {
        exit("発生した課題を入力してください。");
    }

    if (!in_array($status, $allowedStatuses, true)) {
        exit("改善状況が正しくありません。");
    }

    $sql = "UPDATE soccer_posts
            SET match_name = :match_name,
                match_date = :match_date,
                phase = :phase,
                category_id = :category_id,
                issue = :issue,
                cause = :cause,
                improvement = :improvement,
                status = :status
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(":match_name", $matchName, PDO::PARAM_STR);
    $stmt->bindValue(":match_date", $matchDate, PDO::PARAM_STR);
    $stmt->bindValue(":phase", $phase, PDO::PARAM_STR);
    $stmt->bindValue(":category_id", $categoryId, PDO::PARAM_INT);
    $stmt->bindValue(":issue", $issue, PDO::PARAM_STR);
    $stmt->bindValue(":cause", $cause, PDO::PARAM_STR);
    $stmt->bindValue(":improvement", $improvement, PDO::PARAM_STR);
    $stmt->bindValue(":status", $status, PDO::PARAM_STR);
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    
    $result = $stmt->execute();

    if ($result) {
        header("Location: list.php?updated=1");
        exit;
    } else {
        echo "更新に失敗しました。";
        print_r($stmt->errorInfo());
        exit;

    }
}

// 一覧から編集画面を開いた場合
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    exit("投稿番号が正しくありません。");
}

assertOwnership($pdo, $id, (int)$currentUser["id"]);

$sql = "SELECT soccer_posts.*, users.name AS player_name
        FROM soccer_posts
        LEFT JOIN users ON soccer_posts.user_id = users.id
        WHERE soccer_posts.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $id, PDO::PARAM_INT);
$stmt->execute();

$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    exit("指定された投稿が見つかりません。");
}

$sql = "SELECT id, category_name
        FROM categories
        ORDER BY id";

$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>投稿の編集</title>
</head>
<body>
<div class="container">

    <h1>投稿の編集</h1>
    <p class="page-lead">内容の誤字修正や、改善状況の更新に使う画面です。</p>

    <form action="edit.php" method="post" class="post-form">
        <input
            type="hidden"
            name="id"
            value="<?php echo (int)$post["id"]; ?>"
        >

        <div class="form-group">
            <label for="match_name">試合名・対戦相手<span class="required-mark">※必須</span></label>
            
            <input type="text" id="match_name" name="match_name"
            value="<?php echo htmlspecialchars($post["match_name"], ENT_QUOTES, "UTF-8"); ?>"
            required
            >
        </div>

        <div class="form-group">
            <label for="match_date">試合日<span class="required-mark">※必須</span></label>
            
            <input type="date" id="match_date" name="match_date"
            value="<?php echo htmlspecialchars($post["match_date"], ENT_QUOTES, "UTF-8"); ?>"
            required
            >
        </div>

        <div class="form-group">
            <label for="phase">局面<span class="required-mark">※必須</span></label>
            
            <select id="phase" name="phase" required>
                <option value="攻撃"
                <?php if ($post["phase"] === "攻撃") echo "selected"; ?>>
                攻撃
                </option>

                <option value="守備"
                <?php if ($post["phase"] === "守備") echo "selected"; ?>>
                守備
                </option>

                <option value="攻守の切り替え"
                <?php if ($post["phase"] === "攻守の切り替え") echo "selected"; ?>>
                攻守の切り替え
                </option>
            </select>
        </div>

　　　　<div class="form-group">
            <label for="category_id">課題カテゴリー<span class="required-mark">※必須</span></label>

            <select id="category_id" name="category_id" required>
                <option value="">選択してください</option>

                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int)$category["id"]; ?>"
                            <?php if ((int)$post["category_id"] === (int)$category["id"]) {
                                  echo "selected";
                                  }
                            ?>
                    >
                        <?php echo htmlspecialchars($category["category_name"], ENT_QUOTES, "UTF-8"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="issue">発生した課題<span class="required-mark">※必須</span></label>
            
            <textarea id="issue" name="issue" rows="5" required>
                <?php echo htmlspecialchars($post["issue"], ENT_QUOTES, "UTF-8"); ?>
            </textarea>
        </div>

        <div class="form-group">
            <label for="cause">原因(任意)</label>

            <textarea id="cause" name="cause" rows="5">
                <?php echo htmlspecialchars($post["cause"], ENT_QUOTES, "UTF-8"); ?>
            </textarea>
        </div>

        <div class="form-group">
            <label for="improvement">改善案(任意)</label>

            <textarea id="improvement" name="improvement" rows="5">
                <?php echo htmlspecialchars($post["improvement"], ENT_QUOTES, "UTF-8");?>
            </textarea>
        </div>

        <div class="form-group">
            <label for="status">改善状況</label>

            <select name="status" id="status" required>
                <option value="未実施"
                    <?php if ($post["status"] === "未実施") echo "selected"; ?>>
                    未実施
                </option>

                <option value="実践中"
                    <?php if ($post["status"] === "実践中") echo "selected"; ?>>
                    実践中
                </option>

                <option value="達成済み"
                    <?php if ($post["status"] === "達成済み") echo "selected"; ?>>
                    達成済み
                </option>
            </select>
        </div>

        <p class="current-user">
            投稿者：<?php echo htmlspecialchars($post["player_name"], ENT_QUOTES, "UTF-8"); ?>
        </p>

        <button type="submit" class="button-primary">更新する</button>
    </form>

    <a href="detail.php?id=<?php echo (int)$post["id"]; ?>" class="button-secondary-link">投稿詳細へ戻る</a>
    <a href="list.php" class="button-secondary-link">投稿一覧へ戻る</a>

</div>
</body>
</html>