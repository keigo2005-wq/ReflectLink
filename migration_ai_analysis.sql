-- AIによる傾向サマリー機能の追加

CREATE TABLE ai_analyses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    summary TEXT NOT NULL,
    post_count INT NOT NULL,
    generated_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);
