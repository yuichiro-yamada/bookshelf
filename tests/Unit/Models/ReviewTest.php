<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reviewモデルの単体テスト（テストケース一覧 1-3「Reviewモデル」に対応）
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1-3-1 ユーザー・書籍との関連が取得できる
     */
    public function test_user_and_book_relations(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->for($user)->for($book)->create();

        $this->assertTrue($review->user->is($user));
        $this->assertTrue($review->book->is($book));
    }

    /**
     * 1-3-2 いいねしたユーザーとの多対多関連が取得できる
     */
    public function test_liked_by_users_relation_returns_users_who_liked(): void
    {
        $review = Review::factory()->create();
        $users = User::factory()->count(2)->create();
        User::factory()->create(); // いいねしていないユーザー
        $review->likedByUsers()->attach($users);

        $this->assertEqualsCanonicalizing(
            $users->pluck('id')->all(),
            $review->likedByUsers->pluck('id')->all()
        );
    }

    /**
     * 1-3-3 rating属性が整数としてキャストされる
     */
    public function test_rating_is_cast_to_integer(): void
    {
        $review = Review::factory()->create(['rating' => '4']);

        $this->assertSame(4, $review->fresh()->rating);
    }
}
