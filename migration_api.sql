-- 外部連携用のAPIキー機能の追加

ALTER TABLE users
    ADD COLUMN api_key VARCHAR(64) NULL UNIQUE AFTER password_hash;
