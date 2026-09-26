<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Userモデルの単体テスト（テストケース一覧 1-6「Userモデル」に対応）
 *
 * アプリの機能から実際に使われているリレーションを対象にする。
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1-6-1 お気に入り登録した書籍との多対多関連が取得できる
     *
     * FavoriteController（お気に入り一覧・登録／解除）と、書籍詳細のお気に入りボタンで使用している。
     */
    public function test_favorite_books_relation_returns_favorited_books(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create();
        Book::factory()->create(); // お気に入り登録していない書籍
        User::factory()->create()->favoriteBooks()->attach(Book::factory()->create()); // 他のユーザーのお気に入り
        $user->favoriteBooks()->attach($books);

        $this->assertEqualsCanonicalizing(
            $books->pluck('id')->all(),
            $user->favoriteBooks->pluck('id')->all()
        );
    }

    /**
     * 1-6-2 いいねしたレビューとの多対多関連が取得できる
     *
     * 書籍詳細のいいねボタン（いいね済み／未いいねの表示切り替え）で使用している。
     */
    public function test_liked_reviews_relation_returns_liked_reviews(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->count(2)->create();
        Review::factory()->create(); // いいねしていないレビュー
        $user->likedReviews()->attach($reviews);

        $this->assertEqualsCanonicalizing(
            $reviews->pluck('id')->all(),
            $user->likedReviews->pluck('id')->all()
        );
    }

    /**
     * 1-6-3 登録した書籍との1対多関連が取得できる
     *
     * 書籍の登録（画面・API）で、Auth::user()->books()->create() として登録者を設定するために使用している。
     */
    public function test_books_relation_returns_books_registered_by_user(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->for($user)->create();
        Book::factory()->create(); // 他のユーザーが登録した書籍

        $this->assertEqualsCanonicalizing(
            $books->pluck('id')->all(),
            $user->books->pluck('id')->all()
        );

        // リレーション経由で作成すると、user_id が自動的に設定される
        $created = $user->books()->create(['title' => 'リレーション経由の書籍', 'author' => '著者']);
        $this->assertSame($user->id, $created->user_id);
    }

    /**
     * 1-6-4 投稿したレビューとの1対多関連が取得できる
     *
     * マイ読書レポート（ReportController）で、ログインユーザー自身のレビューを取得するために使用している。
     */
    public function test_reviews_relation_returns_reviews_posted_by_user(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->count(2)->for($user)->create();
        Review::factory()->create(); // 他のユーザーのレビュー

        $this->assertEqualsCanonicalizing(
            $reviews->pluck('id')->all(),
            $user->reviews->pluck('id')->all()
        );
    }

    /**
     * 1-6-5 作成した読書計画との1対多関連が取得できる
     *
     * 読書計画の一覧・作成（ReadingPlanController）で、ログインユーザー自身の計画に限定するために使用している。
     */
    public function test_reading_plans_relation_returns_plans_created_by_user(): void
    {
        $user = User::factory()->create();
        $plans = ReadingPlan::factory()->count(2)->for($user)->create();
        ReadingPlan::factory()->create(); // 他のユーザーの計画

        $this->assertEqualsCanonicalizing(
            $plans->pluck('id')->all(),
            $user->readingPlans->pluck('id')->all()
        );
    }
}
