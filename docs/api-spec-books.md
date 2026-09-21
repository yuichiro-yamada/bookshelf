# 書籍API仕様書（サンプル）

対象: Bookshelfアプリ 公開API `/api/v1/books`

このドキュメントは、実装したコード（`app/Http/Controllers/Api/V1/Book/BookController.php` など）をもとに作成したサンプルです。レビュー用の資料として使う場合は、実際のプロジェクトの命名規則やフォーマットに合わせて調整してください。

## 共通仕様

### ベースURL

```
http://localhost/api/v1
```

### 認証方式

- Laravel Sanctumによるトークン認証（Bearer Token）
- 書き込み系（登録・更新・削除）のみ認証が必要（一覧・詳細取得は認証不要）
- トークンを発行するAPIは用意していない。開発・動作確認用には、`sail artisan tinker` で `User::find(1)->createToken('test')->plainTextToken` を実行して取得する
- 認証が必要なエンドポイントには、リクエストヘッダーに以下を付与する

```
Authorization: Bearer {トークン}
Accept: application/json
```

### 共通HTTPステータスコード

| ステータスコード | 意味 | 発生条件 |
|---|---|---|
| 200 OK | 正常終了 | 一覧・詳細取得、更新が成功したとき |
| 201 Created | 作成成功 | 書籍登録が成功したとき |
| 204 No Content | 削除成功 | 書籍削除が成功したとき（レスポンスボディなし） |
| 401 Unauthorized | 未認証 | 認証が必要なエンドポイントに未ログイン状態でアクセスしたとき |
| 403 Forbidden | 認可エラー | 自分が登録していない書籍を更新・削除しようとしたとき |
| 404 Not Found | データが存在しない | 指定したIDの書籍が存在しないとき |
| 422 Unprocessable Entity | バリデーションエラー | リクエストの入力値が不正なとき |

### 共通エラーレスポンス形式

**401 / 403 の場合**

```json
{
    "message": "認証が必要です"
}
```

```json
{
    "message": "この操作を行う権限がありません"
}
```

**404 の場合**

```json
{
    "message": "指定された書籍が見つかりません"
}
```

**422 の場合**

```json
{
    "message": "書籍タイトルを入力してください (and 1 more error)",
    "errors": {
        "title": ["書籍タイトルを入力してください"],
        "isbn": ["ISBNは13桁の数字で入力してください"]
    }
}
```

---

## 1. 書籍一覧を取得する

### エンドポイント

| 項目 | 内容 |
|---|---|
| メソッド | GET |
| URI | `/api/v1/books` |
| 認証 | 不要 |

キーワード検索（タイトル・著者名の部分一致）、ジャンルによる絞り込み、ページネーションに対応する。各書籍にジャンル情報・平均評価・レビュー件数を含める。

### リクエストパラメータ（クエリパラメータ）

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| keyword | string | - | タイトル・著者名を部分一致で検索する | `"夏目"` |
| genre | integer | - | ジャンルIDで絞り込む（存在するジャンルIDのみ） | `2` |
| page | integer | - | ページ番号（デフォルト: 1） | `2` |
| per_page | integer | - | 1ページあたりの件数（デフォルト: 9、最大: 100） | `20` |

### バリデーションエラーメッセージ

| 項目 | ルール | メッセージ |
|---|---|---|
| keyword | string | キーワードは文字列で指定してください |
| keyword | max:255 | キーワードは255文字以内で指定してください |
| genre | integer | ジャンルIDは整数で指定してください |
| genre | exists:genres,id | 指定されたジャンルは存在しません |
| page | integer | ページ番号は整数で指定してください |
| page | min:1 | ページ番号は1以上の値で指定してください |
| per_page | integer | 取得件数は整数で指定してください |
| per_page | min:1 | 取得件数は1以上の値で指定してください |
| per_page | max:100 | 取得件数は100以下の値で指定してください |

### レスポンス（200 OK）

**レスポンス全体**

| 項目 | 型 | 必須 | 説明 |
|---|---|---|---|
| data | array\<Book\> | ○ | 書籍一覧（下記「data配下」参照） |
| links | object | ○ | ページネーション用リンク（下記「links配下」参照） |
| meta | object | ○ | ページネーション情報（下記「meta配下」参照） |

**data配下（Book 1件あたり）**

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| id | integer | ○ | 書籍ID | `1` |
| title | string | ○ | 書籍タイトル | `"こころ"` |
| author | string | ○ | 著者名 | `"夏目漱石"` |
| isbn | string \| null | - | ISBN（13桁。未登録の場合は null） | `"9784101010014"` |
| published_date | string（YYYY-MM-DD） \| null | - | 出版日（未登録の場合は null） | `"1914-04-20"` |
| description | string \| null | - | 書籍の説明 | `"友人の裏切りから..."` |
| image_url | string \| null | - | 書影画像のURL | `"https://example.com/cover.jpg"` |
| user_id | integer | ○ | 登録者のユーザーID | `3` |
| genres | array\<Genre\> | ○ | ジャンル情報（下記参照） | `[{"id":1,"name":"小説"}]` |
| average_rating | number \| null | ○ | 平均評価（小数第1位で丸め。レビューが0件の場合は null） | `4.5` |
| review_count | integer | ○ | レビュー件数 | `12` |

一覧APIのレスポンスには `reviews`（レビュー本文の配列）は含まれない。個別のレビュー内容は詳細APIで取得する。

**Genre（genres配下）**

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| id | integer | ○ | ジャンルID | `1` |
| name | string | ○ | ジャンル名 | `"小説"` |

**links配下**

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| first | string | ○ | 最初のページのURL | `"http://localhost/api/v1/books?page=1"` |
| last | string | ○ | 最後のページのURL | `"http://localhost/api/v1/books?page=5"` |
| prev | string \| null | - | 前のページのURL（1ページ目では null） | `null` |
| next | string \| null | - | 次のページのURL（最終ページでは null） | `"http://localhost/api/v1/books?page=2"` |

**meta配下**

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| current_page | integer | ○ | 現在のページ番号 | `1` |
| from | integer \| null | - | このページの最初のデータの通し番号 | `1` |
| last_page | integer | ○ | 最終ページ番号 | `5` |
| links | array | ○ | ページ番号ごとのリンク情報の配列 | `[{"url":null,"label":"&laquo; Previous","active":false}, ...]` |
| path | string | ○ | ページネーションのベースURL | `"http://localhost/api/v1/books"` |
| per_page | integer | ○ | 1ページあたりの件数 | `9` |
| to | integer \| null | - | このページの最後のデータの通し番号 | `9` |
| total | integer | ○ | 全件数 | `42` |

### レスポンス例

```json
{
    "data": [
        {
            "id": 1,
            "title": "こころ",
            "author": "夏目漱石",
            "isbn": "9784101010014",
            "published_date": "1914-04-20",
            "description": "友人の裏切りから...",
            "image_url": "https://example.com/cover.jpg",
            "user_id": 3,
            "genres": [
                { "id": 1, "name": "小説" }
            ],
            "average_rating": 4.5,
            "review_count": 12
        }
    ],
    "links": {
        "first": "http://localhost/api/v1/books?page=1",
        "last": "http://localhost/api/v1/books?page=5",
        "prev": null,
        "next": "http://localhost/api/v1/books?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "links": [
            { "url": null, "label": "&laquo; Previous", "active": false }
        ],
        "path": "http://localhost/api/v1/books",
        "per_page": 9,
        "to": 9,
        "total": 42
    }
}
```

---

## 2. 書籍詳細を取得する

### エンドポイント

| 項目 | 内容 |
|---|---|
| メソッド | GET |
| URI | `/api/v1/books/{book}` |
| 認証 | 不要 |

指定したIDの書籍を、ジャンル情報とレビュー一覧（投稿者名・評価・コメント・投稿日時、投稿日時の新しい順）付きで取得する。

### パスパラメータ

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| book | integer | ○ | 書籍ID | `1` |

### レスポンス（200 OK）

**レスポンス全体**

| 項目 | 型 | 必須 | 説明 |
|---|---|---|---|
| data | Book | ○ | 書籍詳細（下記参照） |

**data配下**

一覧APIの `data配下` と同じ項目に加えて、以下が含まれる。

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| reviews | array\<Review\> | ○ | レビュー一覧（投稿日時の新しい順、レビューが無ければ空配列） | 下記参照 |

**Review（reviews配下）**

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| id | integer | ○ | レビューID | `5` |
| user_name | string | ○ | 投稿者名 | `"山田太郎"` |
| rating | integer | ○ | 評価（1〜5） | `4` |
| comment | string | ○ | コメント | `"面白かったです"` |
| created_at | string（ISO8601） | ○ | 投稿日時 | `"2026-09-01T12:34:56+09:00"` |

### レスポンス例

```json
{
    "data": {
        "id": 1,
        "title": "こころ",
        "author": "夏目漱石",
        "isbn": "9784101010014",
        "published_date": "1914-04-20",
        "description": "友人の裏切りから...",
        "image_url": "https://example.com/cover.jpg",
        "user_id": 3,
        "genres": [
            { "id": 1, "name": "小説" }
        ],
        "average_rating": 4.5,
        "review_count": 2,
        "reviews": [
            {
                "id": 8,
                "user_name": "鈴木花子",
                "rating": 5,
                "comment": "何度読んでも良い",
                "created_at": "2026-09-05T09:00:00+09:00"
            },
            {
                "id": 5,
                "user_name": "山田太郎",
                "rating": 4,
                "comment": "面白かったです",
                "created_at": "2026-09-01T12:34:56+09:00"
            }
        ]
    }
}
```

### エラーレスポンス

| ステータス | 条件 | メッセージ例 |
|---|---|---|
| 404 Not Found | 指定したIDの書籍が存在しない | `"指定された書籍が見つかりません"` |

---

## 3. 書籍を新規登録する

### エンドポイント

| 項目 | 内容 |
|---|---|
| メソッド | POST |
| URI | `/api/v1/books` |
| 認証 | 必要（Sanctumトークン） |

登録者（`user_id`）はリクエストボディでは指定せず、認証済みユーザーのIDが自動的に設定される。

### リクエストボディ

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| title | string | ○ | 書籍タイトル（最大255文字） | `"坊っちゃん"` |
| author | string | ○ | 著者名（最大255文字） | `"夏目漱石"` |
| isbn | string | - | ISBN（数字13桁、重複不可） | `"9784101010021"` |
| published_date | string（日付） | - | 出版日 | `"1906-04-01"` |
| description | string | - | 書籍の説明（最大1000文字） | `"江戸っ子気質の..."` |
| image_url | string（URL） | - | 書影画像のURL（最大255文字） | `"https://example.com/cover2.jpg"` |
| genres | array\<integer\> | ○ | ジャンルIDの配列（1つ以上、存在するジャンルIDのみ） | `[1, 3]` |

### バリデーションエラーメッセージ

| 項目 | ルール | メッセージ |
|---|---|---|
| title | required | 書籍タイトルを入力してください |
| title | max:255 | 書籍タイトルは255文字以内で入力してください |
| author | required | 著者名を入力してください |
| author | max:255 | 著者名は255文字以内で入力してください |
| isbn | digits:13 | ISBNは13桁の数字で入力してください |
| isbn | unique | このISBNはすでに登録されています |
| published_date | date | 正しい出版日を入力してください |
| description | max:1000 | 説明は1000文字以内で入力してください |
| image_url | url | URL形式で入力してください |
| image_url | max:255 | 画像URLは255文字以内で入力してください |
| genres | required | ジャンルを1つ以上選択してください |
| genres | array | ジャンルの選択肢が不正です |
| genres.* | integer / exists | 選択されたジャンルが正しくありません／選択されたジャンルは存在しません |

### レスポンス（201 Created）

レスポンス構造は「2. 書籍詳細を取得する」の `data配下` と同じ（登録直後のため `reviews` は空配列、`average_rating` は `null`、`review_count` は `0`）。

```json
{
    "data": {
        "id": 15,
        "title": "坊っちゃん",
        "author": "夏目漱石",
        "isbn": "9784101010021",
        "published_date": "1906-04-01",
        "description": "江戸っ子気質の...",
        "image_url": "https://example.com/cover2.jpg",
        "user_id": 3,
        "genres": [
            { "id": 1, "name": "小説" },
            { "id": 3, "name": "青春" }
        ],
        "average_rating": null,
        "review_count": 0,
        "reviews": []
    }
}
```

### エラーレスポンス

| ステータス | 条件 |
|---|---|
| 401 Unauthorized | Sanctumトークンが無い、または無効なとき |
| 422 Unprocessable Entity | バリデーションエラーのとき（上記メッセージ参照） |

---

## 4. 書籍を更新する

### エンドポイント

| 項目 | 内容 |
|---|---|
| メソッド | PUT |
| URI | `/api/v1/books/{book}` |
| 認証 | 必要（Sanctumトークン、かつ書籍の登録者本人のみ） |

### パスパラメータ

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| book | integer | ○ | 書籍ID | `1` |

### リクエストボディ

「3. 書籍を新規登録する」と同じ項目・バリデーションルール（`isbn` の重複チェックは自分自身を除外して判定する）。

### レスポンス（200 OK）

レスポンス構造は「2. 書籍詳細を取得する」と同じ（更新後の内容、既存のレビューも含む）。

### エラーレスポンス

| ステータス | 条件 |
|---|---|
| 401 Unauthorized | Sanctumトークンが無い、または無効なとき |
| 403 Forbidden | 自分が登録していない書籍を更新しようとしたとき |
| 404 Not Found | 指定したIDの書籍が存在しないとき |
| 422 Unprocessable Entity | バリデーションエラーのとき |

---

## 5. 書籍を削除する

### エンドポイント

| 項目 | 内容 |
|---|---|
| メソッド | DELETE |
| URI | `/api/v1/books/{book}` |
| 認証 | 必要（Sanctumトークン、かつ書籍の登録者本人のみ） |

書籍の削除にあわせて、関連するジャンルの紐付け（book_genre）・レビュー（reviews）・お気に入り（favorites）・レビューへのいいね（review_likes）も削除される。

### パスパラメータ

| 項目 | 型 | 必須 | 説明 | 例 |
|---|---|---|---|---|
| book | integer | ○ | 書籍ID | `1` |

### レスポンス（204 No Content）

レスポンスボディなし。

### エラーレスポンス

| ステータス | 条件 |
|---|---|
| 401 Unauthorized | Sanctumトークンが無い、または無効なとき |
| 403 Forbidden | 自分が登録していない書籍を削除しようとしたとき |
| 404 Not Found | 指定したIDの書籍が存在しないとき |
