-- 課題カテゴリーを「局面」と重複しない軸(課題の性質)に変更する
--
-- 手順:
-- 1. 以下のINSERT文で新しいカテゴリーを追加する(既存のカテゴリーはまだ残す)
-- 2. 追加された新カテゴリーのIDを控え、既存の投稿(soccer_posts)のcategory_idを
--    ふさわしい新カテゴリーのIDに手動でUPDATEする
--    例: UPDATE soccer_posts SET category_id = 6 WHERE id = 6;
-- 3. 全ての投稿の付け替えが終わってから、3で末尾のDELETE文を実行して旧カテゴリーを削除する

INSERT INTO categories (category_name) VALUES
    ('コミュニケーション'),
    ('技術'),
    ('判断・認知'),
    ('フィジカル'),
    ('戦術');

-- (ここで手順2を実施してから、以下を実行する)
-- DELETE FROM categories WHERE category_name IN ('守備', '攻撃', '攻守の切り替え');
