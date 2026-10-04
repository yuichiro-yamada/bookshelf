<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bookモデルの単体テスト（テストケース一覧 1-1「Bookモデル」に対応）
 */
class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1-1-1 ジャンルとの多対多関連が取得できる
     */
    public function test_genres_relation_returns_attached_genres(): void
    {
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();
        Genre::factory()->create(); // 紐づけないジャンル
        $book->genres()->attach($genres);

        $this->assertEqualsCanonicalizing(
            $genres->pluck('id')->all(),
            $book->genres->pluck('id')->all()
        );
    }

    /**
     * 1-1-2 レビューとの1対多関連が新しい順に取得できる
     */
    public function test_reviews_relation_returns_reviews_in_newest_order(): void
    {
        $book = Book::factory()->create();
        $oldest = Review::factory()->for($book)->create(['created_at' => '2026-01-01 00:00:00']);
        $newest = Review::factory()->for($book)->create(['created_at' => '2026-03-01 00:00:00']);
        $middle = Review::factory()->for($book)->create(['created_at' => '2026-02-01 00:00:00']);

        $this->assertSame(
            [$newest->id, $middle->id, $oldest->id],
            $book->reviews->pluck('id')->all()
        );
    }

    /**
     * 1-1-3 読書計画との1対多関連が取得できる
     */
    public function test_reading_plans_relation_returns_related_plans(): void
    {
        $book = Book::factory()->create();
        $plans = ReadingPlan::factory()->count(2)->for($book)->create();
        ReadingPlan::factory()->create(); // 別の書籍の計画

        $this->assertEqualsCanonicalizing(
            $plans->pluck('id')->all(),
            $book->readingPlans->pluck('id')->all()
        );
    }
}
