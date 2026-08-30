<?php

namespace App\Http\Controllers\Book;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\BookRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BookController extends Controller
{
    /**
     * 書籍一覧画面を表示する
     */
    public function index(): View
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(9);

        return view('books.index', compact('books'));
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
     * 書籍を登録する
     */
    public function store(BookRequest $request): RedirectResponse
    {
        $this->authorize('create', Book::class);

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
        $this->authorize('update', $book);

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
