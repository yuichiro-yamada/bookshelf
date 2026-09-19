# バリデーション仕様書

FormRequestクラスごとに、「実際にどのエンドポイント・どのコントローラーメソッドで使われているか」を軸にまとめたものです。

以前の版では「利用元」を画面のBladeファイル名で記載していましたが、Bladeファイル（`create.blade.php`・`edit.blade.php`など）はフォームを**表示するだけ**の画面であり、実際にFormRequestのバリデーションが動くのはフォーム送信を受け取る`store`・`update`などのメソッドです。そのため本版では、次の2列で管理します。

- **エンドポイント**：HTTPメソッド＋URI（Postmanやブラウザから実際に叩くときの情報）
- **利用箇所**：コントローラークラス＠メソッド名（コード上で実際にFormRequestが使われている場所）

`BookRequest`のように、画面（Blade経由のフォーム送信）とAPIの両方から使われているFormRequestは、エンドポイント・利用箇所の両方に画面用・API用を併記し、末尾に（画面）・（API）を付けて区別しています。

---

## RegisterRequest.php

| エンドポイント | 利用箇所 |
|---|---|
| POST /register | `AuthController@register` |

| フォーム | バリデーションルール | エラーメッセージ |
|---|---|---|
| ユーザー名 | 必須<br>最大20文字 | お名前を入力してください<br>お名前は20文字以内で入力してください |
| メールアドレス | 必須<br>メール形式<br>最大255文字<br>unique | メールアドレスを入力してください<br>メールアドレスはメール形式で入力してください<br>メールアドレスは255文字以内で入力してください<br>このメールアドレスはすでに登録されています |
| パスワード | 必須<br>8文字以上<br>最大20文字 | パスワードを入力してください<br>パスワードは8文字以上で入力してください<br>パスワードは20文字以内で入力してください |
| 確認用パスワード | 必須<br>パスワードと一致 | 確認用パスワードを入力してください<br>パスワードが一致しません |

---

## LoginRequest.php

`rules()`による形式チェックと、`authenticate()`による認証情報チェックの2段階になっています。`authenticate()`はLaravelが自動で呼ぶメソッドではなく、`AuthController@login`の中で`$request->authenticate()`として明示的に呼び出されている点に注意してください。

| エンドポイント | 利用箇所 |
|---|---|
| POST /login | `AuthController@login`<br>（`rules()`の形式チェック通過後、同メソッド内で`authenticate()`を呼び出して認証情報を確認） |

| フォーム | バリデーションルール | エラーメッセージ |
|---|---|---|
| メールアドレス | 必須<br>メール形式 | メールアドレスを入力してください<br>メールアドレスはメール形式で入力してください |
| パスワード（形式チェック／`rules()`） | 必須 | パスワードを入力してください |
| パスワード（認証チェック／`authenticate()`） | 登録されている会員情報と一致すること | 会員情報が登録されていません |

---

## BookRequest.php

**このFormRequestは画面とAPIの両方から使われています。** 見た目上は両方とも`BookController`ですが、実際には別々の名前空間の別クラスです（画面用: `App\Http\Controllers\Book\BookController`、API用: `App\Http\Controllers\Api\V1\Book\BookController`）。バリデーションルール・エラーメッセージは完全に共通で、1つの`BookRequest`クラスを両方のコントローラーが`use`しています。

| エンドポイント | 利用箇所 |
|---|---|
| POST /books | `Book\BookController@store`（画面） |
| PUT /books/{book} | `Book\BookController@update`（画面） |
| POST /api/v1/books | `Api\V1\Book\BookController@store`（API） |
| PUT /api/v1/books/{book} | `Api\V1\Book\BookController@update`（API） |

| フォーム | バリデーションルール | エラーメッセージ |
|---|---|---|
| 書籍タイトル | 必須<br>最大255文字 | 書籍タイトルを入力してください<br>書籍タイトルは255文字以内で入力してください |
| 著者名 | 必須<br>最大255文字 | 著者名を入力してください<br>著者名は255文字以内で入力してください |
| ISBN | 任意<br>数字13桁<br>unique | ISBNは13桁の数字で入力してください<br>このISBNはすでに登録されています |
| 出版日 | 任意<br>日付 | 正しい出版日を入力してください |
| 説明 | 最大1000文字 | 説明は1000文字以内で入力してください |
| 画像URL | URL形式<br>最大255文字 | URL形式で入力してください<br>画像URLは255文字以内で入力してください |
| ジャンル | 1つ以上選択<br>配列形式であること<br>配列の内容が全て整数<br>genresテーブルのidに存在する | ジャンルを1つ以上選択してください<br>ジャンルの選択肢が不正です<br>選択されたジャンルが正しくありません<br>選択されたジャンルは存在しません |

※ `isbn`のunique判定は、更新時（`update`）のみ自分自身のレコードを除外して判定する（`Rule::unique(...)->ignore($book)`）。画面用・API用どちらのルートも、パラメータ名が`{book}`で揃っているためこの仕組みがそのまま動作する。

---

## SearchIsbnRequest.php

ISBNはリクエストボディではなくURLの一部（ルートパラメータ`{isbn}`）として渡されるため、`prepareForValidation()`でルートパラメータをバリデーション対象に取り込んでいます。また、`failedValidation()`をオーバーライドしており、バリデーション失敗時のレスポンス形式が他のFormRequestとは異なります（下記参照）。

| エンドポイント | 利用箇所 |
|---|---|
| GET /books/isbn/{isbn} | `Book\BookController@searchIsbn`（画面・Ajax） |

| フォーム | バリデーションルール | エラーメッセージ |
|---|---|---|
| ISBN | 必須<br>数字13桁<br>unique | ISBNを入力してください<br>ISBNは13桁の数字で入力してください<br>このISBNはすでに登録されています |

※ バリデーション失敗時は、他のFormRequestのような`{"message":..., "errors": {...}}`形式ではなく、`{"error": "（エラーメッセージ）"}`という独自形式で422を返す（`books/create.blade.php`側のJavaScriptがこのキーを見て判定しているため）。

---

## ReviewRequest.php

| エンドポイント | 利用箇所 |
|---|---|
| POST /books/{book}/reviews | `ReviewController@store`（画面） |
| PUT /reviews/{review} | `ReviewController@update`（画面） |

| フォーム | バリデーションルール | エラーメッセージ |
|---|---|---|
| 評価 | 必須<br>整数<br>1〜5の間 | 評価を入力してください<br>評価は1〜5の整数で入力してください<br>評価は1〜5の整数で入力してください |
| コメント | 最大255文字 | コメントは255文字以内で入力してください |

---

## GenreRequest.php

| エンドポイント | 利用箇所 |
|---|---|
| POST /genres | `GenreController@store`（画面） |
| PUT /genres/{genre} | `GenreController@update`（画面） |

| フォーム | バリデーションルール | エラーメッセージ |
|---|---|---|
| ジャンル名 | 必須<br>最大20文字<br>unique | ジャンル名を入力してください<br>ジャンル名は20文字以内で入力してください<br>このジャンル名はすでに登録されています |

※ uniqueの判定は、更新時（`update`）のみ自分自身のレコードを除外して判定する（`Rule::unique('genres', 'name')->ignore($genre)`）。

---

## ReadingPlanRequest.php

編集画面では期日のみ変更できる仕様のため、`book_id`のバリデーションは新規作成時のみ適用され、更新時には行われません（`rules()`内で`$this->route('readingPlan')`の有無によって条件分岐している）。

| エンドポイント | 利用箇所 |
|---|---|
| POST /reading-plans | `ReadingPlanController@store`（画面） |
| PUT /reading-plans/{readingPlan} | `ReadingPlanController@update`（画面） |

| フォーム | バリデーションルール | エラーメッセージ | 適用対象 |
|---|---|---|---|
| 書籍ID | 必須<br>整数<br>存在確認 | 書籍を選択してください<br>書籍の指定が正しくありません<br>選択された書籍が見つかりません | 新規作成時のみ |
| 期日 | 必須<br>日付形式<br>今日以降の日付 | 期日を入力してください<br>正しい日付を入力してください<br>期日には今日以降の日付を指定してください | 新規作成・更新時共通 |

---

## BookIndexRequest.php（API専用・新規）

画面側の書籍一覧表示（`Book\BookController@index`）には対応するFormRequestが存在せず、`$request->input()`で直接パラメータを受け取っているだけのため、これはAPI専用のFormRequestです。

| エンドポイント | 利用箇所 |
|---|---|
| GET /api/v1/books | `Api\V1\Book\BookController@index`（API） |

| フォーム | バリデーションルール | エラーメッセージ |
|---|---|---|
| キーワード | 文字列<br>最大255文字 | キーワードは文字列で指定してください<br>キーワードは255文字以内で指定してください |
| ジャンルID | 整数<br>genresテーブルのidに存在する | ジャンルIDは整数で指定してください<br>指定されたジャンルは存在しません |
| ページ番号 | 整数<br>1以上 | ページ番号は整数で指定してください<br>ページ番号は1以上の値で指定してください |
| 取得件数 | 整数<br>1以上100以下 | 取得件数は整数で指定してください<br>取得件数は1以上の値で指定してください<br>取得件数は100以下の値で指定してください |

---

## TokenRequest.php（API専用・新規）

`rules()`による形式チェックと、`authenticate()`による認証情報チェックの2段階になっています（`LoginRequest`と同じ構成）。ただし`LoginRequest::authenticate()`はセッションを使う`Auth::attempt()`でログインするのに対し、こちらはAPIにセッションがないため、会員情報との照合だけを行い、ログイン状態は作りません。`authenticate()`は`TokenController@store`の中で明示的に呼び出されています。

| エンドポイント | 利用箇所 |
|---|---|
| POST /api/v1/auth/token | `Api\V1\Auth\TokenController@store`（API）<br>（`rules()`の形式チェック通過後、同メソッド内で`authenticate()`を呼び出して認証情報を確認） |

| フォーム | バリデーションルール | エラーメッセージ |
|---|---|---|
| メールアドレス | 必須<br>文字列<br>メール形式 | メールアドレスを入力してください<br>メールアドレスはメール形式で入力してください |
| パスワード（形式チェック／`rules()`） | 必須<br>文字列 | パスワードを入力してください |
| パスワード（認証チェック／`authenticate()`） | 登録されている会員情報と一致すること | 会員情報が登録されていません |
| トークン名（device_name） | 任意<br>文字列<br>最大255文字 | トークン名は255文字以内で入力してください |
