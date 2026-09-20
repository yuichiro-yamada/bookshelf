<?php

namespace App\Http\Controllers\Genre;

use App\Http\Controllers\Controller;
use App\Http\Requests\Genre\GenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧画面を表示する
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')->orderBy('id')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面を表示する
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを登録する
     */
    public function store(GenreRequest $request): RedirectResponse
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを登録しました。');
    }

    /**
     * ジャンルに紐づく書籍一覧を表示する
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()->with('genres')->paginate(9);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面を表示する
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンルを更新する
     */
    public function update(GenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました。');
    }

    /**
     * ジャンルを削除する
     *
     * book_genreテーブルの外部キー制約（genre_id側はcascadeOnDelete）により、
     * このジャンルに紐づく書籍がある場合も、書籍自体は削除されず
     * そのジャンルとの紐付けだけが自動的に削除される。
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        $genre->delete();

        return back()->with('success', 'ジャンルを削除しました。');
    }
}
