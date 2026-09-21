# ReflectLink API仕様

外部システム(スマホアプリ等)からReflectLinkのデータを利用するためのJSON REST API。

## 認証

`api_settings.php` で発行したAPIキーを、リクエストヘッダーに付与する。

```
Authorization: Bearer <APIキー>
```

## エンドポイント

### GET /api/posts.php

投稿一覧を取得する。

パラメータ：`limit`(任意、デフォルト20、最大100)

レスポンス例：
```json
{
  "posts": [
    {
      "id": 2,
      "match_name": "経大戦",
      "match_date": "2026-09-15",
      "phase": "守備",
      "category_name": "守備",
      "status": "実践中",
      "player_name": "山田花子",
      "created_at": "2026-09-20 23:09:00"
    }
  ],
  "count": 1
}
```

### GET /api/posts.php?id={id}

投稿1件の詳細(コメント含む)を取得する。

### POST /api/comments.php

投稿にコメントを追加する。

リクエストボディ(JSON)：
```json
{
  "post_id": 2,
  "comment": "コメント本文"
}
```

## エラー形式

```json
{ "error": "エラーメッセージ" }
```

| ステータス | 意味 |
|---|---|
| 400 | リクエスト形式が不正 |
| 401 | APIキーが無効・未指定 |
| 404 | 指定した投稿が存在しない |
| 405 | 許可されていないHTTPメソッド |
