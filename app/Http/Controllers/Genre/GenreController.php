<?php

namespace App\Http\Controllers\Genre;

use App\Http\Controllers\Controller;
use App\Http\Requests\Genre\GenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     *
     * ジャンル詳細へは「ジャンル一覧」と「マイ読書レポート」の2画面から遷移できるため、
     * 遷移元をクエリパラメータ from で受け取り、「戻る」リンクの遷移先・文言を切り替える
     * （from=reports の場合はマイ読書レポート、それ以外はジャンル一覧へ戻る）。
     */
    public function show(Request $request, Genre $genre): View
    {
        // 書籍一覧と同じく登録日時の新しい順（同じ日時の場合は ID の新しい順）に並べる。
        // 中間テーブル book_genre にも created_at があるため、books テーブルのカラムと明示する。
        // ページ送りしても from が引き継がれるよう withQueryString() を付ける
        $books = $genre->books()
            ->with('genres')
            ->orderByDesc('books.created_at')
            ->orderByDesc('books.id')
            ->paginate(10)
            ->withQueryString();

        [$backUrl, $backLabel] = $request->query('from') === 'reports'
            ? [route('reports.index'), 'マイ読書レポートに戻る']
            : [route('genres.index'), 'ジャンル一覧に戻る'];

        return view('genres.show', compact('genre', 'books', 'backUrl', 'backLabel'));
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
     * 書籍に紐づいているジャンルは削除できない。
     * book_genreテーブルの外部キー制約（genre_id側はrestrictOnDelete）でも
     * DBレベルで削除が拒否されるが、ここで事前に判定して
     * ユーザーにメッセージを表示する。
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        if ($genre->books()->exists()) {
            return back()->with('error', 'このジャンルは書籍に紐づいているため削除できません。');
        }

        $genre->delete();

        return back()->with('success', 'ジャンルを削除しました。');
    }
}
