<?php

require_once "includes/db.php";
require_once "includes/auth.php";

requireLogin($pdo);

$sql = "
    SELECT
        soccer_posts.id,
        soccer_posts.match_name,
        soccer_posts.match_date,
        soccer_posts.phase,
        soccer_posts.category_id,
        soccer_posts.issue,
        soccer_posts.cause,
        soccer_posts.improvement,
        soccer_posts.status,
        users.name AS player_name,
        soccer_posts.created_at,
        soccer_posts.image_name
    FROM soccer_posts
    LEFT JOIN users ON soccer_posts.user_id = users.id
    ORDER BY soccer_posts.created_at DESC, soccer_posts.id DESC
";

$stmt = $pdo->query($sql);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSVファイルとしてダウンロードさせるための設定
$filename = "soccer_posts_" . date("Ymd_His") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

// CSVを書き出す場所を開く
$output = fopen("php://output", "w");

// Excelで日本語が文字化けしにくいようにする
fwrite($output, "\xEF\xBB\xBF");

// CSVの見出し
fputcsv($output, [
    "投稿番号",
    "試合名",
    "試合日",
    "フェーズ",
    "カテゴリID",
    "発生した課題",
    "原因",
    "改善案",
    "ステータス",
    "選手名",
    "投稿日時",
    "画像ファイル名"
]);

// 投稿を1行ずつCSVに書き出す
foreach ($posts as $post) {
    fputcsv($output, [
        $post["id"],
        $post["match_name"],
        $post["match_date"],
        $post["phase"],
        $post["category_id"],
        $post["issue"],
        $post["cause"],
        $post["improvement"],
        $post["status"],
        $post["player_name"],
        $post["created_at"],
        $post["image_name"]
    ]);
}

fclose($output);
exit;