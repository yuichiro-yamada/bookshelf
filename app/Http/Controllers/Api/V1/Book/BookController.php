<?php

namespace App\Http\Controllers\Api\V1\Book;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Book\BookIndexRequest;
use App\Http\Requests\Book\BookRequest;
use App\Http\Resources\Api\V1\Book\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得する
     *
     * GET /api/v1/books
     *
     * キーワード検索（タイトル・著者名）、ジャンル絞り込み、ページネーションに対応する。
     * 各書籍にジャンル情報・平均評価（average_rating）・レビュー件数（review_count）を含める。
     */
    public function index(BookIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $keyword = $validated['keyword'] ?? '';
        $genreId = $validated['genre'] ?? null;
        $perPage = $validated['per_page'] ?? 9;

        // キーワード検索・ジャンル絞り込みは、画面用コントローラー（Book\BookController）
        // と共通のロジックを Book モデルのローカルスコープ（searchKeyword・filterByGenre）に
        // 切り出して使っている。
        // created_at が同一秒内で重複しうるため id を第2キーにして並び順を一意に確定させる
        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->searchKeyword($keyword)
            ->filterByGenre($genreId)
            ->latest()->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return BookResource::collection($books);
    }

    /**
     * 書籍詳細を取得する
     *
     * GET /api/v1/books/{book}
     *
     * ジャンル情報とレビュー（投稿者名・評価・コメント・投稿日時、新しい順）を含める。
     * 存在しない ID の場合は、ルートモデルバインディングにより自動的に 404 が返る。
     */
    public function show(Book $book): BookResource
    {
        // Book::reviews() は latest() 済みの定義になっているため、
        // 読み込むだけで投稿日時の新しい順に並ぶ。
        $book->load(['genres', 'reviews.user'])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 書籍を新規登録する
     *
     * POST /api/v1/books
     *
     * Sanctum 認証必須。登録者（user_id）はリクエストボディではなく
     * 認証済みユーザー（Auth::id()）から設定する。
     */
    public function store(BookRequest $request): JsonResponse
    {
        // 認可（BookPolicy::create）はBookRequest::authorize()側で行っている
        $validated = $request->validated();

        $book = Book::create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'published_date' => $validated['published_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'user_id' => Auth::id(),
        ]);

        $book->genres()->sync($validated['genres']);

        $book->load(['genres', 'reviews.user'])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍を更新する
     *
     * PUT /api/v1/books/{book}
     *
     * Sanctum 認証必須。書籍の所有者本人のみ更新できる（BookPolicy::update）。
     * 存在しない ID の場合は、ルートモデルバインディングにより自動的に 404 が返る。
     */
    public function update(BookRequest $request, Book $book): BookResource
    {
        // 認可（BookPolicy::update）はBookRequest::authorize()側で行っている
        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'published_date' => $validated['published_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres']);

        $book->load(['genres', 'reviews.user'])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 書籍を削除する
     *
     * DELETE /api/v1/books/{book}
     *
     * Sanctum 認証必須。書籍の所有者本人のみ削除できる（BookPolicy::delete）。
     * book_genre・reviews・favorites は books への外部キーに
     * cascadeOnDelete が設定されているため、$book->delete() だけで
     * 関連レコードもまとめて削除される
     * （reviews に紐づく review_likes も reviews 側の cascadeOnDelete でさらに連鎖して削除される）。
     * 存在しない ID の場合は、ルートモデルバインディングにより自動的に 404 が返る。
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
