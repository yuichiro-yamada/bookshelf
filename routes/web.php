<?php

use App\Http\Controllers\Book\BookController;
use App\Http\Controllers\Favorite\FavoriteController;
use App\Http\Controllers\Genre\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Ranking\RankingController;
use App\Http\Controllers\ReadingPlan\ReadingPlanController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Review\ReviewController;
use Illuminate\Support\Facades\Route;

// トップページ
Route::get('/', [BookController::class, 'index'])->name('books.index');

// 書籍登録画面の表示
Route::get('/books/create', [BookController::class, 'create'])->name('books.create')->middleware('auth');

// ISBNから書籍情報を取得（Ajax用）
Route::get('/books/isbn/{isbn}', [BookController::class, 'searchIsbn'])->name('books.isbn')->middleware('auth');

// 書籍の登録処理（ログイン必須）
Route::post('/books', [BookController::class, 'store'])->name('books.store')->middleware('auth');

// 書籍詳細画面の表示
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// 評価ランキング
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');

// ジャンル登録画面の表示・登録処理（ログイン必須）
// ※ /genres/{genre} より前に定義する（そうしないと "create" が {genre} として解釈されてしまう）
Route::get('/genres/create', [GenreController::class, 'create'])->name('genres.create')->middleware('auth');
Route::post('/genres', [GenreController::class, 'store'])->name('genres.store')->middleware('auth');

// ジャンル一覧・詳細（ログイン必須）
Route::get('/genres', [GenreController::class, 'index'])->name('genres.index')->middleware('auth');
Route::get('/genres/{genre}', [GenreController::class, 'show'])->name('genres.show')->middleware('auth');

// ログイン済みのユーザーのみアクセス可能
Route::middleware('auth')->group(function () {
    // マイ読書レポート
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // 読書計画一覧・登録・編集・読了・削除（ログインユーザー自身の計画のみ）
    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])->name('reading-plans.index');
    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])->name('reading-plans.create');
    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])->name('reading-plans.store');
    Route::get('/reading-plans/{readingPlan}/edit', [ReadingPlanController::class, 'edit'])->name('reading-plans.edit');
    Route::put('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'update'])->name('reading-plans.update');
    Route::post('/reading-plans/{readingPlan}/complete', [ReadingPlanController::class, 'complete'])->name('reading-plans.complete');
    Route::delete('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'destroy'])->name('reading-plans.destroy');

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

    // 通知機能
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

});
