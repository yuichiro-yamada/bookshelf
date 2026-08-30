<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー削除機能のテスト（テストケース一覧「レビュー削除」に対応）
 */
class ReviewDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_sees_delete_button(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        Review::factory()->for($book)->for($owner)->create();

        $ownerResponse = $this->actingAs($owner)->get(route('books.show', $book));
        $ownerResponse->assertSee('削除');

        $otherResponse = $this->actingAs($otherUser)->get(route('books.show', $book));
        $otherResponse->assertDontSee('削除');
    }

    public function test_other_user_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($otherUser)->delete(route('reviews.destroy', $review));

        $response->assertForbidden();
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_owner_can_delete_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for(User::factory())->create();
        $review = Review::factory()->for($book)->for($owner)->create();

        $response = $this->actingAs($owner)->delete(route('reviews.destroy', $review));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'レビューを削除しました。');
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
}
