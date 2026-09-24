# ポリシー仕様書

Policyクラスごとに、「どのメソッドが」「どんな条件で許可されるか」「実際にどのエンドポイント・どのコントローラーメソッドから呼ばれているか」をまとめたものです。`docs/validation-spec.md`（バリデーション仕様書）と対になるドキュメントです。

- **許可条件**：Policyメソッドの中身（trueを返す条件）
- **呼び出し箇所**：エンドポイント（HTTPメソッド＋URI）＋コントローラークラス＠メソッド名

判定に失敗した場合は、いずれのPolicyメソッドも`AuthorizationException`（403）になります。画面側は403エラーページ、API側は`app/Exceptions/Handler.php`のrenderableにより日本語メッセージ付きのJSON（`この操作を行う権限がありません`）が返ります。

---

## BookPolicy.php

対象モデル: `Book`

| メソッド | 許可条件 | 呼び出し箇所 |
|---|---|---|
| create | ログインユーザーであれば誰でも許可（常にtrue） | POST /books → `Book\BookController@store`（画面）<br>POST /api/v1/books → `Api\V1\Book\BookController@store`（API） |
| update | 書籍の登録者本人のみ（`$user->id === $book->user_id`） | GET /books/{book}/edit → `Book\BookController@edit`（画面）<br>PUT /books/{book} → `Book\BookController@update`（画面）<br>PUT /api/v1/books/{book} → `Api\V1\Book\BookController@update`（API） |
| delete | 書籍の登録者本人のみ（`$user->id === $book->user_id`） | DELETE /books/{book} → `Book\BookController@destroy`（画面）<br>DELETE /api/v1/books/{book} → `Api\V1\Book\BookController@destroy`（API） |

※ `store`・`update`（画面・API共通）は、`BookRequest::authorize()`内の`can('create', Book::class)` / `can('update', $book)`のみでこのPolicyを判定する。コントローラー側には`$this->authorize()`を置かず、二重判定にならないようにしている。一方`edit`・`delete`はFormRequestを経由しないため、コントローラー冒頭の`$this->authorize()`が唯一の判定箇所になる。

---

## ReviewPolicy.php

対象モデル: `Review`

| メソッド | 許可条件 | 呼び出し箇所 |
|---|---|---|
| create | 対象書籍に対して、そのユーザーがまだレビューを投稿していないこと（1書籍につき1ユーザー1件まで） | POST /books/{book}/reviews → `Review\ReviewController@store` |
| update | レビューの投稿者本人のみ | GET /reviews/{review}/edit → `Review\ReviewController@edit`<br>PUT /reviews/{review} → `Review\ReviewController@update` |
| delete | レビューの投稿者本人のみ | DELETE /reviews/{review} → `Review\ReviewController@destroy` |

※ `create`の第2引数は`Book`モデル（`$this->authorize('create', [Review::class, $book])`）。「1書籍につき1ユーザー1件まで」という制約は、このPolicyに加えて`reviews`テーブルの複合UNIQUE制約（`user_id`+`book_id`）でもDBレベルで担保されている。

---

## ReadingPlanPolicy.php

対象モデル: `ReadingPlan`

| メソッド | 許可条件 | 呼び出し箇所 |
|---|---|---|
| create | 対象書籍について、そのユーザーの「進行中」の計画がまだ存在しないこと | POST /reading-plans → `ReadingPlan\ReadingPlanController@store` |
| update | 計画の作成者本人であること。加えてステータスにより制限：<br>・完了 → 常に不可<br>・期限切れ → `create`と同じ判定（同一書籍で他に進行中の計画がなければ可）<br>・進行中 → 可 | GET /reading-plans/{readingPlan}/edit → `ReadingPlan\ReadingPlanController@edit`<br>PUT /reading-plans/{readingPlan} → `ReadingPlan\ReadingPlanController@update` |
| complete | 計画の作成者本人であること。かつステータスが「完了」でないこと | POST /reading-plans/{readingPlan}/complete → `ReadingPlan\ReadingPlanController@complete` |
| delete | 計画の作成者本人のみ | DELETE /reading-plans/{readingPlan} → `ReadingPlan\ReadingPlanController@destroy` |

※ `create`の第2引数は`Book`モデル（`$this->authorize('create', [ReadingPlan::class, $book])`）。「進行中の計画は書籍ごとに1ユーザー1件まで」という制約を実現している。
※ `update`の「期限切れ」分岐は`create()`をそのまま再利用している（期限切れの計画を更新すると「進行中」に戻るため、進行中の計画が重複しないようにするため）。

---

## Policyが存在しないケース：ジャンル削除

`Genre`にはPolicyクラスが存在しません。ジャンル削除（DELETE /genres/{genre} → `Genre\GenreController@destroy`）の制限は、「誰が操作できるか」という認可の問題ではなく、「書籍に紐づいているかどうか」というデータ整合性の問題のため、コントローラー内で直接`$genre->books()->exists()`をチェックする実装になっている。あわせて、DB側にも`book_genre.genre_id`への`restrictOnDelete`制約があり、アプリ側のチェックをすり抜けた場合の保険としてDBレベルでも削除を拒否する。

---

## モデルとポリシーの対応（AuthServiceProvider）

| モデル | ポリシー |
|---|---|
| Book | BookPolicy |
| Review | ReviewPolicy |
| ReadingPlan | ReadingPlanPolicy |

`app/Providers/AuthServiceProvider.php`の`$policies`配列で明示的に紐付けられている。
