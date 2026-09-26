<?php

namespace Tests\Feature\PublicApi;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍削除APIのテスト（テストケース一覧 10-6「書籍削除API(DELETE /api/v1/books/{book})」に対応）
 */
class BookApiDestroyTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    /**
     * 10-6-1 認証なしでアクセスすると401が返る
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertUnauthorized();
        $response->assertExactJson(['message' => '認証が必要です']);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * 10-6-2 自分が登録した書籍を削除できる
     */
    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->withToken($this->tokenFor($owner))->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();
        $this->assertSame('', $response->getContent());
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * 10-6-3 書籍を削除すると、関連するデータも一緒に削除される
     */
    public function test_related_data_is_deleted_together(): void
    {
        $owner = User::factory()->create();
        $fan = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->for($owner)->create();
        $book->genres()->attach($genre);
        $review = Review::factory()->for($book)->create();
        $fan->favoriteBooks()->attach($book);
        $review->likedByUsers()->attach($fan);

        $response = $this->withToken($this->tokenFor($owner))->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
        // ジャンル自体は削除されない
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    /**
     * 10-6-4 他のユーザーが登録した書籍は削除できない
     */
    public function test_other_user_cannot_delete_book(): void
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->withToken($this->tokenFor($otherUser))->deleteJson("/api/v1/books/{$book->id}");

        $response->assertForbidden();
        $response->assertExactJson(['message' => 'この操作を行う権限がありません']);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * 10-6-5 存在しないIDを指定すると404が返る
     */
    public function test_non_existent_book_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->withToken($this->tokenFor($user))->deleteJson('/api/v1/books/999');

        $response->assertNotFound();
        $response->assertExactJson(['message' => '指定された書籍が見つかりません']);
    }
}
