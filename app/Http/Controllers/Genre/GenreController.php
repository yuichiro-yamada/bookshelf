<?php

namespace App\Http\Controllers\Genre;

use App\Http\Controllers\Controller;
use App\Http\Requests\Genre\GenreRequest;
use App\Models\Genre;
use Illuminate\Database\QueryException;
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
     * book_genresテーブルの外部キー制約（genre_id側はrestrictOnDelete）により、
     * このジャンルに紐づく書籍が1件でも存在する場合、DB側が削除を拒否してくる。
     * その場合はエラーメッセージを表示して元の画面に戻す。
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        try {
            $genre->delete();
        } catch (QueryException $e) {
            // SQLSTATE 23000: 整合性制約違反（外部キー制約違反を含む）
            if ($e->getCode() === '23000') {
                return back()->with('error', 'このジャンルは書籍に紐づいているため削除できません。');
            }

            // 制約違反以外のエラーは想定外なのでそのまま投げる
            throw $e;
        }

        return back()->with('success', 'ジャンルを削除しました。');
    }
}
