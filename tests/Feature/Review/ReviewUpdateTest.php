<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー編集機能のテスト（テストケース一覧「レビュー編集」に対応）
 */
class ReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_sees_edit_link(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        Review::factory()->for($book)->for($owner)->create();

        $ownerResponse = $this->actingAs($owner)->get(route('books.show', $book));
        $ownerResponse->assertSee('編集');

        $otherResponse = $this->actingAs($otherUser)->get(route('books.show', $book));
        $otherResponse->assertDontSee('編集');
    }

    public function test_edit_page_shows_current_rating_and_comment(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create(['comment' => '編集前コメント']);

        $response = $this->actingAs($owner)->get(route('reviews.edit', $review));

        $response->assertOk();
        $response->assertSee('編集前コメント');
    }

    public function test_rating_is_required(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($owner)->put(route('reviews.update', $review), [
            'rating' => '',
            'comment' => 'コメント',
        ]);

        $response->assertSessionHasErrors(['rating' => '評価を入力してください']);
    }

    public function test_comment_must_not_exceed_255_characters(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($owner)->put(route('reviews.update', $review), [
            'rating' => 4,
            'comment' => str_repeat('あ', 256),
        ]);

        $response->assertSessionHasErrors(['comment' => 'コメントは255文字以内で入力してください']);
    }

    public function test_other_user_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($otherUser)->put(route('reviews.update', $review), [
            'rating' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_review_is_updated_with_valid_data(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($owner)->put(route('reviews.update', $review), [
            'rating' => 2,
            'comment' => '更新後のコメント',
        ]);

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを更新しました。');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 2, 'comment' => '更新後のコメント']);
    }
}
