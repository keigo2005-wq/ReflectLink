-- ユーザー登録・ログイン機能の追加に伴うマイグレーション
-- 既存のsoccer_posts.player_name / comments.commenter_name, position を
-- usersテーブルへの外部キー(user_id)に置き換える。

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    position VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE soccer_posts
    ADD COLUMN user_id INT NULL AFTER player_name;

ALTER TABLE soccer_posts
    ADD CONSTRAINT fk_soccer_posts_user
    FOREIGN KEY (user_id) REFERENCES users(id);

-- 既存の投稿データが残っている場合は、DROP COLUMNの前に
-- 各行のuser_idを実際のユーザーに手動で割り当てておくこと。
-- 例: UPDATE soccer_posts SET user_id = 1 WHERE user_id IS NULL;
ALTER TABLE soccer_posts
    DROP COLUMN player_name;

ALTER TABLE soccer_posts
    MODIFY COLUMN user_id INT NOT NULL;

ALTER TABLE comments
    ADD COLUMN user_id INT NULL AFTER post_id;

ALTER TABLE comments
    ADD CONSTRAINT fk_comments_user
    FOREIGN KEY (user_id) REFERENCES users(id);

-- 既存のコメントが残っている場合は、DROP COLUMNの前に
-- 各行のuser_idを実際のユーザーに手動で割り当てておくこと。
ALTER TABLE comments
    DROP COLUMN commenter_name;

ALTER TABLE comments
    DROP COLUMN position;

ALTER TABLE comments
    MODIFY COLUMN user_id INT NOT NULL;
