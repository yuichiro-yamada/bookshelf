<?php

namespace App\Http\Controllers\Favorite;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧画面を表示する
     */
    public function index(): View
    {
        // お気に入りに登録した日時の新しい順（同じ日時の場合は登録の新しい順）に並べる
        $books = Auth::user()->favoriteBooks()
            ->orderByPivot('created_at', 'desc')
            ->orderByPivot('id', 'desc')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * お気に入りの登録・解除を切り替える
     */
    public function toggle(Book $book): RedirectResponse
    {
        $user = Auth::user();

        if ($user->favoriteBooks->contains($book->id)) {
            $user->favoriteBooks()->detach($book->id);
        } else {
            $user->favoriteBooks()->attach($book->id);
        }

        return back();
    }
}
