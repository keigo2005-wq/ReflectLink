<?php
require_once("db.php");
require_once("functions.php");
require_once("auth.php");

$currentUser = requireLogin($pdo);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>試合課題の投稿</title>
</head>
<body>
    <div class="container">
    <?php

    $sql = "SELECT id, category_name FROM categories ORDER BY id";
    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $message = "";
    $imageName = null;

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $matchName   = $_POST["match_name"];
        $matchDate   = $_POST["match_date"];
        $phase       = $_POST["phase"];
        $category_id =  (int)$_POST["category_id"];
        $issue       = $_POST["issue"];
        $cause       = $_POST["cause"];
        $improvement = $_POST["improvement"];
        $status = $_POST["status"];

        if (
        empty($matchName) ||
        empty($matchDate) ||
        empty($phase) ||
        empty($category_id) ||
        empty($issue) ||
        empty($status)
        ) {
            $message = "必須項目をすべて入力してください。";
        } else {
            if (isset($_FILES["play_image"]) && $_FILES["play_image"]["error"] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES["play_image"]["error"] !== UPLOAD_ERR_OK) {
                    $message = "画像のアップロードに失敗しました。";
                } else {
                    $allowedTypes = ["image/jpeg" => "jpg", "image/png"  => "png"];

                    $imageType = mime_content_type($_FILES["play_image"]["tmp_name"]);

                    if (!isset($allowedTypes[$imageType])) {
                        $message = "JPEGまたはPNG画像を選択してください。";
                    } elseif ($_FILES["play_image"]["size"] > 5 * 1024 * 1024) {
                        $message = "画像は5MB以下にしてください。";
                    } else {
                        $extension = $allowedTypes[$imageType];
                        $imageName = uniqid("play_", true) . "." . $extension;
                        $savePath = __DIR__ . "/uploads/" . $imageName;

                        if (!move_uploaded_file($_FILES["play_image"]["tmp_name"],$savePath)) {
                            $message = "画像を保存できませんでした。";
                        }
                    }
                }
            }
            
            if (empty($message)) {
                $sql = "INSERT INTO soccer_posts
                    (match_name, match_date, phase, category_id, issue, cause, improvement, status, user_id, image_name)
                    VALUES
                    (:match_name, :match_date, :phase, :category_id, :issue, :cause, :improvement, :status, :user_id, :image_name)";

                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(":match_name", $matchName, PDO::PARAM_STR);
                $stmt->bindParam(":match_date", $matchDate, PDO::PARAM_STR);
                $stmt->bindParam(":phase", $phase, PDO::PARAM_STR);
                $stmt->bindValue(":category_id", $category_id, PDO::PARAM_INT);
                $stmt->bindParam(":issue", $issue, PDO::PARAM_STR);
                $stmt->bindParam(":cause", $cause, PDO::PARAM_STR);
                $stmt->bindParam(":improvement", $improvement, PDO::PARAM_STR);
                $stmt->bindValue(":status", $status, PDO::PARAM_STR);
                $stmt->bindValue(":user_id", $currentUser["id"], PDO::PARAM_INT);
                $stmt->bindValue(":image_name", $imageName, PDO::PARAM_STR);

                $stmt->execute();

                $message = "投稿を保存しました。";
            }
        }
    }
    
    ?>

    <?php if (!empty($message)): ?>
        <p class="message">
            <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
        </p>
    <?php endif; ?>


    <h1>試合課題の投稿</h1>

    <p class="current-user">
        投稿者：<?= escape($currentUser["name"]) ?>(<?= escape($currentUser["position"]) ?>)
        ｜<a href="logout.php">ログアウト</a>
    </p>

    <form action="post.php" method="post" enctype="multipart/form-data" class="post-form">
        <div class="form-group">
            <label for="match_name">試合名・対戦相手<span class="required-mark">※必須</span></label>
            <input
                type="text"
                id="match_name"
                name="match_name"
            >
        </div>

        <div class="form-group">
            <label for="match_date">試合日<span class="required-mark">※必須</span></label>
            <input
                type="date"
                id="match_date"
                name="match_date"
            >
        </div>

        <div class="form-group">
            <label for="phase">局面<span class="required-mark">※必須</span></label>
            <select id="phase" name="phase">
                <option value="">選択してください</option>
                <option value="攻撃">攻撃</option>
                <option value="守備">守備</option>
                <option value="攻守の切り替え">攻守の切り替え</option>
            </select>
        </div>

        <div class="form-group">
            <label for="category_id">課題カテゴリー<span class="required-mark">※必須</span></label>
            <select id="category_id" name="category_id" required>
                <option value="">選択してください</option>

                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category["id"]; ?>">
                        <?php echo htmlspecialchars($category["category_name"],ENT_QUOTES,"UTF-8"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="issue">発生した課題<span class="required-mark">※必須</span></label>
            <textarea id="issue" name="issue" rows="5" maxlength="500"></textarea>
            <p class="character-count">
                <span id="issue-count">0</span> / 500文字
            </p>
        </div>

        <div class="form-group">
            <label for="cause">原因(任意・ミーティングで話し合ってから後で追記してもよい)</label>
            <textarea id="cause" name="cause" rows="5" maxlength="500"></textarea>
            <p class="character-count">
                <span id="cause-count">0</span> / 500文字
            </p>
        </div>

        <div class="form-group">
            <label for="improvement">改善案(任意・ミーティングで話し合ってから後で追記してもよい)</label>
            <textarea id="improvement" name="improvement" rows="5" maxlength="500"></textarea>
            <p class="character-count">
                <span id="improvement-count">0</span> / 500文字
            </p>
        </div>

        <div class="form-group">
            <label for="status">改善状況</label>
            <select id="status" name="status" required>
                 <option value="未実施" selected>未実施</option>
   　　　　　　　　　　　 <option value="実践中">実践中</option>
                 <option value="達成済み">達成済み</option>
            </select>
        </div>

        <div class="form-group image-form-group">
            <label for="play_image">プレー画像（JPEG・PNG）</label>
            <input type="file" id="play_image" name="play_image" accept="image/jpeg, image/png">
        <div>

        <div class="form-actions">
            <button type="submit" class="post-submit-button">投稿する</button>
            <a href="list.php">投稿一覧を見る</a>
        </div>
    </form>
    
    
    
    <script src="character-count.js?v=2"></script>
</body>
</html>