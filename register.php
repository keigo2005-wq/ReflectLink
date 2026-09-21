<?php

require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/auth.php";

$message = "";
$positions = ["GK", "DF", "MF", "FW", "監督・スタッフ"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $passwordConfirm = $_POST["password_confirm"] ?? "";
    $position = $_POST["position"] ?? "";

    if (
        $name === "" ||
        $email === "" ||
        $password === "" ||
        !in_array($position, $positions, true)
    ) {
        $message = "すべての項目を正しく入力してください。";
    } elseif (strlen($password) < 8) {
        $message = "パスワードは8文字以上で設定してください。";
    } elseif ($password !== $passwordConfirm) {
        $message = "パスワードが一致しません。";
    } else {
        $checkSql = "SELECT id FROM users WHERE email = :email";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindValue(":email", $email);
        $checkStmt->execute();

        if ($checkStmt->fetch()) {
            $message = "このメールアドレスは既に登録されています。";
        } else {
            $sql = "INSERT INTO users (name, email, password_hash, position)
                    VALUES (:name, :email, :password_hash, :position)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":name", $name);
            $stmt->bindValue(":email", $email);
            $stmt->bindValue(":password_hash", password_hash($password, PASSWORD_DEFAULT));
            $stmt->bindValue(":position", $position);
            $stmt->execute();

            $_SESSION["user_id"] = (int)$pdo->lastInsertId();

            header("Location: list.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>新規登録</title>
</head>
<body>
    <div class="container">
        <h1>新規登録</h1>

        <?php if (!empty($message)): ?>
            <p class="message"><?= escape($message) ?></p>
        <?php endif; ?>

        <form action="register.php" method="post" class="post-form">
            <div class="form-group">
                <label for="name">名前<span class="required-mark">※必須</span></label>
                <input type="text" id="name" name="name" maxlength="100" required>
                <p class="character-count">他の部員から見て分かるよう、本名を正しい表記（漢字）で入力してください。</p>
            </div>

            <div class="form-group">
                <label for="email">メールアドレス<span class="required-mark">※必須</span></label>
                <input type="email" id="email" name="email" maxlength="255" required>
            </div>

            <div class="form-group">
                <label for="position">ポジション<span class="required-mark">※必須</span></label>
                <select id="position" name="position" required>
                    <option value="">選択してください</option>
                    <?php foreach ($positions as $positionOption): ?>
                        <option value="<?= escape($positionOption) ?>">
                            <?= escape($positionOption) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="password">パスワード(8文字以上)<span class="required-mark">※必須</span></label>
                <input type="password" id="password" name="password" minlength="8" required>
            </div>

            <div class="form-group">
                <label for="password_confirm">パスワード(確認)<span class="required-mark">※必須</span></label>
                <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="post-submit-button">登録する</button>
                <a href="login.php">ログインはこちら</a>
            </div>
        </form>
    </div>
</body>
</html>
