<?php

namespace Tests\Feature\PublicApi;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍詳細取得APIのテスト（テストケース一覧 10-3「書籍詳細取得API(GET /api/v1/books/{book})」に対応）
 */
class BookApiShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 10-3-1 認証なしでもアクセスでき、書籍の詳細が返る
     */
    public function test_guest_can_get_book_detail(): void
    {
        $genre = Genre::factory()->create(['name' => 'ミステリー']);
        $book = Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784000000000',
            'published_date' => '2000-01-01',
        ]);
        $book->genres()->attach($genre);
        Review::factory()->for($book)->create(['rating' => 3]);
        Review::factory()->for($book)->create(['rating' => 4]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.title', '吾輩は猫である');
        $response->assertJsonPath('data.author', '夏目漱石');
        $response->assertJsonPath('data.isbn', '9784000000000');
        $response->assertJsonPath('data.published_date', '2000-01-01');
        $response->assertJsonPath('data.genres', [['id' => $genre->id, 'name' => 'ミステリー']]);
        $response->assertJsonPath('data.average_rating', 3.5);
        $response->assertJsonPath('data.review_count', 2);
    }

    /**
     * 10-3-2 レビュー(投稿者名・評価・コメント・投稿日時)が新しい順に含まれる
     */
    public function test_reviews_are_included_in_newest_order(): void
    {
        $book = Book::factory()->create();
        $oldReview = Review::factory()->for($book)->for(User::factory()->create(['name' => '古い投稿者']))->create([
            'rating' => 2,
            'comment' => '古いコメント',
            'created_at' => '2026-01-01 10:00:00',
        ]);
        $newReview = Review::factory()->for($book)->for(User::factory()->create(['name' => '新しい投稿者']))->create([
            'rating' => 5,
            'comment' => '新しいコメント',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();
        $response->assertJsonCount(2, 'data.reviews');
        $response->assertJsonPath('data.reviews.0.id', $newReview->id);
        $response->assertJsonPath('data.reviews.0.user_name', '新しい投稿者');
        $response->assertJsonPath('data.reviews.0.rating', 5);
        $response->assertJsonPath('data.reviews.0.comment', '新しいコメント');
        $response->assertJsonPath('data.reviews.0.created_at', $newReview->created_at->toIso8601String());
        $response->assertJsonPath('data.reviews.1.id', $oldReview->id);
        $response->assertJsonPath('data.reviews.1.user_name', '古い投稿者');
    }

    /**
     * 10-3-3 存在しないIDを指定すると404が返る
     */
    public function test_non_existent_book_returns_404(): void
    {
        $response = $this->getJson('/api/v1/books/999');

        $response->assertNotFound();
        $response->assertExactJson(['message' => '指定された書籍が見つかりません']);
    }
}
