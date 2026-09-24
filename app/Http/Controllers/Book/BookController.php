<?php

namespace App\Http\Controllers\Book;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\BookRequest;
use App\Http\Requests\Book\SearchIsbnRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BookController extends Controller
{
    /**
     * 書籍一覧画面を表示する
     *
     * キーワード検索（タイトル・著者名）、ジャンル絞り込み、並び替えに対応する。
     */
    public function index(Request $request): View
    {
        $keyword = $request->input('keyword', '');
        $genreId = $request->input('genre');
        $sort = $request->input('sort', 'latest');

        // キーワード検索・ジャンル絞り込みは、API用コントローラー（Api\V1\Book\BookController）
        // と共通のロジックを Book モデルのローカルスコープ（searchKeyword・filterByGenre）に
        // 切り出して使っている。
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->searchKeyword($keyword)
            ->filterByGenre($genreId);

        // created_at は同一秒内にまとめて登録されたデータだと値が重複しうるため、
        // id を第2キーにして並び順を一意に確定させる（id は登録順と一致する）。
        match ($sort) {
            'oldest' => $query->oldest()->oldest('id'),
            'title' => $query->orderBy('title')->orderBy('id'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->latest()->latest('id'),
            'latest' => $query->latest()->latest('id'),
            default => $query->latest()->latest('id'),
        };

        $books = $query->paginate(10)->withQueryString();

        $genres = Genre::orderBy('name')->get();

        $hasAnyBooks = Book::query()->exists();

        return view('books.index', compact('books', 'genres', 'hasAnyBooks'));
    }

    /**
     * 書籍詳細画面を表示する
     */
    public function show(Book $book): View
    {
        $book->load(['genres', 'user', 'reviews.user', 'reviews.likedByUsers']);

        $hasReviewed = Auth::check() && $book->reviews->contains('user_id', Auth::id());

        return view('books.show', compact('book', 'hasReviewed'));
    }

    /**
     * 書籍登録画面を表示する
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * ISBNからGoogle Books APIで書籍情報を取得する（Ajax用）
     */
    public function searchIsbn(SearchIsbnRequest $request, string $isbn): \Illuminate\Http\JsonResponse
    {
        $response = Http::get(config('services.google_books.url'), [
            'q' => 'isbn:' . $isbn,
            'key' => config('services.google_books.key'),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => '書籍情報の取得中にエラーが発生しました。'], 502);
        }

        $items = $response->json('items', []);

        $matched = collect($items)->first(function ($item) use ($isbn) {
            $identifiers = $item['volumeInfo']['industryIdentifiers'] ?? [];
            return collect($identifiers)->contains(
                fn ($id) => ($id['identifier'] ?? null) === $isbn
            );
        });

        if (!$matched) {
            return response()->json(['error' => '該当する書籍が見つかりませんでした。'], 404);
        }

        $volumeInfo = $matched['volumeInfo'] ?? [];

        return response()->json([
            'title' => $volumeInfo['title'] ?? null,
            'author' => isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : null,
            'description' => $volumeInfo['description'] ?? null,
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
            'published_date' => $volumeInfo['publishedDate'] ?? null,
        ]);
    }

    /**
     * 書籍を登録する
     */
    public function store(BookRequest $request): RedirectResponse
    {
        // 認可（BookPolicy::create）はBookRequest::authorize()側で行っている
        $validated = $request->validated();

        $book = Book::create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'user_id' => Auth::id(),
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.index')->with('success', "「{$book->title}」を登録しました。");
    }

    /**
     * 書籍編集画面を表示する
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍情報を更新する
     */
    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        // 認可（BookPolicy::update）はBookRequest::authorize()側で行っている
        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.show', $book)->with('success', "「{$book->title}」の情報を更新しました。");
    }

    /**
     * 書籍を削除する
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }

}
