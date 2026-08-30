<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Book\BookController;
use App\Http\Controllers\Genre\GenreController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Favorite\FavoriteController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Ranking\RankingController;

// トップページ
Route::get('/', [BookController::class, 'index'])->name('books.index');

// 書籍登録画面の表示
Route::get('/books/create', [BookController::class, 'create'])->name('books.create')->middleware('auth');

// 書籍の保存処理（通常、次に必要になります）
Route::post('/books', [BookController::class, 'store'])->name('books.store')->middleware('auth');

// 書籍詳細画面の表示
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// 評価ランキング
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');

// ジャンル登録画面の表示・登録処理（ログイン必須）
// ※ /genres/{genre} より前に定義する（そうしないと "create" が {genre} として解釈されてしまう）
Route::get('/genres/create', [GenreController::class, 'create'])->name('genres.create')->middleware('auth');
Route::post('/genres', [GenreController::class, 'store'])->name('genres.store')->middleware('auth');

// ジャンル一覧・詳細（未ログインでも閲覧可能）
Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
Route::get('/genres/{genre}', [GenreController::class, 'show'])->name('genres.show');

// 未ログインのユーザーのみアクセス可能（会員登録・ログイン）
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// ログイン済みのユーザーのみアクセス可能
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // お気に入り一覧・登録・解除
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{book}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // 書籍の編集・削除
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    // ジャンルの編集・削除
    Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])->name('genres.edit');
    Route::put('/genres/{genre}', [GenreController::class, 'update'])->name('genres.update');
    Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');

    // レビューの投稿・いいね
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/reviews/{review}/like', [ReviewController::class, 'like'])->name('reviews.like');

    // レビューの編集・削除
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});
