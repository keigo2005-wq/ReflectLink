-- 行動計画・練習メニュー機能(action-plan-service)用のテーブル
-- soccer_posts.idを参照するが、別サービス(Java)が管理するテーブルのため
-- 外部キー制約は付けず、アプリケーション側で存在チェックを行う。

CREATE TABLE action_plans (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    action_text TEXT NOT NULL,
    practice_menu TEXT,
    due_date DATE,
    status VARCHAR(20) NOT NULL DEFAULT '未着手',
    created_by_name VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
