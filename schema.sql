-- ReflectLink データベーススキーマ(完成形)
-- 新規に環境を作る場合は、このファイルを丸ごと実行すればよい。

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    api_key VARCHAR(64) NULL UNIQUE,
    position VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL
);

CREATE TABLE soccer_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_name VARCHAR(255) NOT NULL,
    match_date DATE NOT NULL,
    phase VARCHAR(50) NOT NULL,
    category_id INT NOT NULL,
    issue TEXT NOT NULL,
    cause TEXT,
    improvement TEXT,
    status VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    image_name VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES soccer_posts(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE ai_analyses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    summary TEXT NOT NULL,
    post_count INT NOT NULL,
    generated_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- 個人の目標・行動・自己評価(goal-serviceというJavaサービスが管理)
CREATE TABLE goals (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal TEXT NOT NULL,
    action_text TEXT NOT NULL,
    result TEXT,
    status VARCHAR(20) NOT NULL DEFAULT '未着手',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories (category_name) VALUES
    ('コミュニケーション'),
    ('技術'),
    ('判断・認知'),
    ('フィジカル'),
    ('戦術');
