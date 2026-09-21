<?php

session_start();

function currentUser(PDO $pdo)
{
    if (empty($_SESSION["user_id"])) {
        return null;
    }

    $sql = "SELECT id, name, email, position FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":id", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        unset($_SESSION["user_id"]);
        return null;
    }

    return $user;
}

function requireLogin(PDO $pdo)
{
    $user = currentUser($pdo);

    if (!$user) {
        header("Location: login.php");
        exit;
    }

    return $user;
}
