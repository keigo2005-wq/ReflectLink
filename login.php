<?php

require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/auth.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $message = "メールアドレスとパスワードを入力してください。";
    } else {
        $sql = "SELECT id, password_hash FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":email", $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user["password_hash"])) {
            $message = "メールアドレスまたはパスワードが正しくありません。";
        } else {
            $_SESSION["user_id"] = (int)$user["id"];

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
    <title>ログイン</title>
</head>
<body>
    <div class="container">
        <h1>ログイン</h1>

        <?php if (!empty($message)): ?>
            <p class="message"><?= escape($message) ?></p>
        <?php endif; ?>

        <form action="login.php" method="post" class="post-form">
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" maxlength="255" required>
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="post-submit-button">ログイン</button>
                <a href="register.php">新規登録はこちら</a>
            </div>
        </form>
    </div>
</body>
</html>
