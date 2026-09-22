-- goal-serviceを本番(Render + PostgreSQL)で動かす際に、
-- Renderが発行したPostgreSQLデータベースに対して一度だけ実行するスキーマ。
-- ローカル開発(MySQL/MariaDB)ではリポジトリ直下のschema.sqlのgoalsテーブル定義を使う。

CREATE TABLE goals (
    id BIGSERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    goal TEXT NOT NULL,
    action_text TEXT NOT NULL,
    result TEXT,
    status VARCHAR(20) NOT NULL DEFAULT '未着手',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
